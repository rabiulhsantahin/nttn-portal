<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Link;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ServiceRequestController extends Controller
{
    public function index()
    {
        $requests = ServiceRequest::with(['link.customer', 'link.reseller', 'creator'])
            ->latest()
            ->paginate(15);

        return view('service_requests.index', compact('requests'));
    }

    public function store(Request $request, Link $link)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:UPGRADE,DOWNGRADE'],
            'requested_bandwidth_mbps' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
        ]);

        $requestNo = 'SR-' . date('Y') . '-' . strtoupper(Str::random(5));

        $sr = ServiceRequest::create([
            'request_no' => $requestNo,
            'link_id' => $link->id,
            'type' => $validated['type'],
            'old_bandwidth_mbps' => $link->bandwidth_mbps,
            'requested_bandwidth_mbps' => $validated['requested_bandwidth_mbps'],
            'reason' => $validated['reason'],
            'status' => ServiceRequest::STATUS_PENDING,
            'created_by' => Auth::id(),
        ]);

        ActivityLog::record(
            entityType: 'service_request',
            entityId: $sr->id,
            action: 'BANDWIDTH_CHANGE_REQUESTED',
            fromStatus: null,
            toStatus: ServiceRequest::STATUS_PENDING,
            remarks: "{$validated['type']} requested from {$link->bandwidth_mbps} Mbps to {$validated['requested_bandwidth_mbps']} Mbps",
            userId: Auth::id()
        );

        return redirect()->route('links.show', $link)
            ->with('success', "Bandwidth {$validated['type']} request {$requestNo} submitted!");
    }

    public function decide(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject,implement'],
        ]);

        $action = $validated['action'];
        $oldStatus = $serviceRequest->status;

        if ($action === 'approve') {
            $serviceRequest->update(['status' => ServiceRequest::STATUS_APPROVED]);

            ActivityLog::record(
                entityType: 'service_request',
                entityId: $serviceRequest->id,
                action: 'BANDWIDTH_CHANGE_APPROVED',
                fromStatus: $oldStatus,
                toStatus: ServiceRequest::STATUS_APPROVED,
                remarks: "Approved by management. Ready for NOC re-provisioning.",
                userId: Auth::id()
            );

            return back()->with('success', 'Bandwidth request approved. Assigned to NOC for implementation.');
        }

        if ($action === 'implement') {
            $link = $serviceRequest->link;
            $link->update(['bandwidth_mbps' => $serviceRequest->requested_bandwidth_mbps]);
            $serviceRequest->update(['status' => ServiceRequest::STATUS_COMPLETED]);

            ActivityLog::record(
                entityType: 'service_request',
                entityId: $serviceRequest->id,
                action: 'BANDWIDTH_CHANGE_IMPLEMENTED',
                fromStatus: $oldStatus,
                toStatus: ServiceRequest::STATUS_COMPLETED,
                remarks: "NOC updated port policer & committed bandwidth to {$serviceRequest->requested_bandwidth_mbps} Mbps",
                userId: Auth::id()
            );

            return back()->with('success', "Bandwidth updated on live circuit to {$serviceRequest->requested_bandwidth_mbps} Mbps!");
        }

        if ($action === 'reject') {
            $serviceRequest->update(['status' => ServiceRequest::STATUS_REJECTED]);

            ActivityLog::record(
                entityType: 'service_request',
                entityId: $serviceRequest->id,
                action: 'BANDWIDTH_CHANGE_REJECTED',
                fromStatus: $oldStatus,
                toStatus: ServiceRequest::STATUS_REJECTED,
                remarks: "Rejected by management",
                userId: Auth::id()
            );

            return back()->with('warning', 'Bandwidth modification rejected.');
        }
    }
}
