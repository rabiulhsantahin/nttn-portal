<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Billing;
use App\Models\Link;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ServiceRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = ServiceRequest::with(['link.customer', 'link.reseller', 'link.billing', 'creator']);

        if ($user->isReseller()) {
            $query->whereHas('link', fn ($q) => $q->where('reseller_id', $user->reseller?->id ?? 0));
        }

        $requests = $query->latest()->paginate(15);

        return view('service_requests.index', compact('requests'));
    }

    public function store(Request $request, Link $link)
    {
        $user = Auth::user();

        if ($user->isReseller() && $link->reseller_id !== $user->reseller?->id) {
            abort(403, 'Unauthorized access to this link.');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:UPGRADE,DOWNGRADE,DISCONTINUE'],
            'requested_bandwidth_mbps' => ['required_unless:type,DISCONTINUE', 'nullable', 'integer', 'min:0'],
            'reason' => ['nullable', 'string'],
        ]);

        $type = $validated['type'];

        if ($type === ServiceRequest::TYPE_DISCONTINUE) {
            if (! $link->canBeDiscontinued()) {
                $eligibleDate = $link->discontinuationEligibleDate();
                $dateStr = $eligibleDate ? $eligibleDate->format('M d, Y') : '3 months from activation';

                return back()->with('error', "Link discontinuation is locked during the initial 3-month service commitment. Eligible after {$dateStr}.");
            }
            $targetBandwidth = 0;
            $initialStatus = ServiceRequest::STATUS_PENDING;
            $suggestedMonthlyCharge = 0.00;
        } else {
            $targetBandwidth = (int) $validated['requested_bandwidth_mbps'];
            $initialStatus = ServiceRequest::STATUS_PENDING_BILLING;

            // Suggest proportional monthly charge based on existing billing
            $currentMonthly = $link->billing?->monthly_charge;
            if ($currentMonthly !== null && $link->bandwidth_mbps > 0 && $targetBandwidth > 0) {
                $suggestedMonthlyCharge = round(($currentMonthly / $link->bandwidth_mbps) * $targetBandwidth, 2);
            } else {
                $suggestedMonthlyCharge = $currentMonthly ?? 0.00;
            }
        }

        $requestNo = 'SR-'.date('Y').'-'.strtoupper(Str::random(5));

        $sr = ServiceRequest::create([
            'request_no' => $requestNo,
            'link_id' => $link->id,
            'type' => $type,
            'old_bandwidth_mbps' => $link->bandwidth_mbps,
            'requested_bandwidth_mbps' => $targetBandwidth,
            'new_monthly_charge' => $suggestedMonthlyCharge,
            'reason' => $validated['reason'],
            'status' => $initialStatus,
            'created_by' => Auth::id(),
        ]);

        $remarks = $type === ServiceRequest::TYPE_DISCONTINUE
            ? "Link discontinuation requested for circuit {$link->circuit_id} (Reason: ".($validated['reason'] ?? 'Not specified').')'
            : "{$type} requested from {$link->bandwidth_mbps} Mbps to {$targetBandwidth} Mbps. Sent to Billing department for commercial review.";

        ActivityLog::record(
            entityType: 'service_request',
            entityId: $sr->id,
            action: $type === ServiceRequest::TYPE_DISCONTINUE ? 'LINK_DISCONTINUE_REQUESTED' : 'BANDWIDTH_CHANGE_REQUESTED',
            fromStatus: null,
            toStatus: $initialStatus,
            remarks: $remarks,
            userId: Auth::id()
        );

        $msg = $type === ServiceRequest::TYPE_DISCONTINUE
            ? "Link discontinuation request {$requestNo} submitted!"
            : "Bandwidth {$type} request {$requestNo} submitted and forwarded to Billing department!";

        return redirect()->route('links.show', $link)->with('success', $msg);
    }

    public function decide(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject,implement,billing_approve,md_approve,tx_implement'],
            'new_monthly_charge' => ['nullable', 'numeric', 'min:0'],
            'tx_remarks' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $action = $validated['action'];
        $oldStatus = $serviceRequest->status;
        $user = Auth::user();

        // 1. Billing Approval
        if ($action === 'billing_approve' || ($action === 'approve' && $oldStatus === ServiceRequest::STATUS_PENDING_BILLING)) {
            if (! ($user->isBilling() || $user->isSuperAdmin())) {
                abort(403, 'Only Billing Department or Super Admin can approve commercial terms.');
            }

            $newCharge = $request->filled('new_monthly_charge')
                ? (float) $request->input('new_monthly_charge')
                : ($serviceRequest->new_monthly_charge ?? 0.00);

            $serviceRequest->update([
                'new_monthly_charge' => $newCharge,
                'status' => ServiceRequest::STATUS_PENDING_MD,
            ]);

            ActivityLog::record(
                entityType: 'service_request',
                entityId: $serviceRequest->id,
                action: 'BANDWIDTH_BILLING_APPROVED',
                fromStatus: $oldStatus,
                toStatus: ServiceRequest::STATUS_PENDING_MD,
                remarks: 'Billing approved commercial adjustment. New Monthly Charge: ৳'.number_format($newCharge, 2).($request->filled('remarks') ? ' ('.$request->input('remarks').')' : '').'. Escalated to MD for approval.',
                userId: $user->id
            );

            return back()->with('success', 'Commercial terms approved (৳'.number_format($newCharge, 2).')! Escalated to MD for approval.');
        }

        // 2. MD Approval
        if ($action === 'md_approve' || ($action === 'approve' && $oldStatus === ServiceRequest::STATUS_PENDING_MD)) {
            if (! ($user->isMd() || $user->isSuperAdmin())) {
                abort(403, 'Only MD or Super Admin can approve this request.');
            }

            $serviceRequest->update([
                'status' => ServiceRequest::STATUS_PENDING_TX,
            ]);

            ActivityLog::record(
                entityType: 'service_request',
                entityId: $serviceRequest->id,
                action: 'BANDWIDTH_MD_APPROVED',
                fromStatus: $oldStatus,
                toStatus: ServiceRequest::STATUS_PENDING_TX,
                remarks: 'Approved by MD. Forwarded to Transmission (TX) team to coordinate with NTTN and update bandwidth.'.($request->filled('remarks') ? ' Remarks: '.$request->input('remarks') : ''),
                userId: $user->id
            );

            return back()->with('success', 'Bandwidth request approved by MD! Forwarded to Transmission team for NTTN coordination.');
        }

        // 3. Transmission (TX) Team NTTN Coordination & Final Implementation
        if ($action === 'tx_implement' || $action === 'implement' || ($action === 'approve' && $oldStatus === ServiceRequest::STATUS_PENDING_TX)) {
            if (! ($user->isTransmission() || $user->isNoc() || $user->isSuperAdmin())) {
                abort(403, 'Only Transmission / NOC team or Super Admin can implement circuit changes.');
            }

            $link = $serviceRequest->link;

            if ($serviceRequest->type === ServiceRequest::TYPE_DISCONTINUE) {
                $link->update([
                    'status' => Link::STATUS_TERMINATED,
                    'bandwidth_mbps' => 0,
                ]);

                if ($link->billing) {
                    $link->billing->update([
                        'monthly_charge' => 0,
                        'status' => Billing::STATUS_COMPLETED,
                    ]);
                }
            } else {
                $link->update(['bandwidth_mbps' => $serviceRequest->requested_bandwidth_mbps]);

                // Direct finalization of live billing
                $finalMonthly = $serviceRequest->new_monthly_charge ?? $link->billing?->monthly_charge ?? 0;
                if ($link->billing) {
                    $link->billing->update([
                        'monthly_charge' => $finalMonthly,
                        'status' => Billing::STATUS_COMPLETED,
                    ]);
                } else {
                    Billing::create([
                        'link_id' => $link->id,
                        'installation_charge' => 0,
                        'monthly_charge' => $finalMonthly,
                        'nttn_cost' => 0,
                        'billing_start_date' => now(),
                        'due_date' => now()->addDays(15),
                        'invoice_no' => 'INV-'.date('Y').'-'.strtoupper(Str::random(6)),
                        'status' => Billing::STATUS_COMPLETED,
                        'completed_by' => $user->id,
                    ]);
                }
            }

            $txRemarks = $request->input('tx_remarks') ?? $request->input('remarks');

            $serviceRequest->update([
                'status' => ServiceRequest::STATUS_COMPLETED,
                'tx_remarks' => $txRemarks ?? $serviceRequest->tx_remarks,
            ]);

            ActivityLog::record(
                entityType: 'service_request',
                entityId: $serviceRequest->id,
                action: $serviceRequest->type === ServiceRequest::TYPE_DISCONTINUE ? 'LINK_DISCONTINUED_TERMINATED' : 'BANDWIDTH_CHANGE_IMPLEMENTED',
                fromStatus: $oldStatus,
                toStatus: ServiceRequest::STATUS_COMPLETED,
                remarks: $serviceRequest->type === ServiceRequest::TYPE_DISCONTINUE
                    ? "Transmission/NOC de-commissioned live circuit {$link->circuit_id} and terminated link."
                    : "Transmission completed NTTN coordination. Bandwidth updated to {$serviceRequest->requested_bandwidth_mbps} Mbps on circuit {$link->circuit_id} and monthly bill finalized at ৳".number_format($serviceRequest->new_monthly_charge ?? 0, 2).($txRemarks ? " (Remarks: {$txRemarks})" : ''),
                userId: $user->id
            );

            return back()->with('success', $serviceRequest->type === ServiceRequest::TYPE_DISCONTINUE
                ? "Circuit {$link->circuit_id} has been de-commissioned and marked as TERMINATED."
                : 'NTTN coordination completed! Circuit bandwidth updated to '.$serviceRequest->requested_bandwidth_mbps.' Mbps and live billing finalized at ৳'.number_format($serviceRequest->new_monthly_charge ?? 0, 2).'!');
        }

        // 4. Fallback legacy approve for DISCONTINUE or PENDING
        if ($action === 'approve') {
            if (! ($user->isMd() || $user->isSuperAdmin())) {
                abort(403, 'Unauthorized.');
            }

            $serviceRequest->update(['status' => ServiceRequest::STATUS_APPROVED]);

            ActivityLog::record(
                entityType: 'service_request',
                entityId: $serviceRequest->id,
                action: $serviceRequest->type === ServiceRequest::TYPE_DISCONTINUE ? 'LINK_DISCONTINUE_APPROVED' : 'BANDWIDTH_CHANGE_APPROVED',
                fromStatus: $oldStatus,
                toStatus: ServiceRequest::STATUS_APPROVED,
                remarks: 'Approved by management. Ready for NOC / Transmission implementation.',
                userId: $user->id
            );

            return back()->with('success', 'Service request approved. Assigned for implementation.');
        }

        // 5. Reject
        if ($action === 'reject') {
            $serviceRequest->update(['status' => ServiceRequest::STATUS_REJECTED]);

            ActivityLog::record(
                entityType: 'service_request',
                entityId: $serviceRequest->id,
                action: $serviceRequest->type === ServiceRequest::TYPE_DISCONTINUE ? 'LINK_DISCONTINUE_REJECTED' : 'BANDWIDTH_CHANGE_REJECTED',
                fromStatus: $oldStatus,
                toStatus: ServiceRequest::STATUS_REJECTED,
                remarks: 'Rejected by '.$user->name.($request->filled('remarks') ? ': '.$request->input('remarks') : ''),
                userId: $user->id
            );

            return back()->with('warning', 'Service modification request rejected.');
        }
    }
}
