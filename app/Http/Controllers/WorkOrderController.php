<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Link;
use App\Models\NocHandover;
use App\Models\Role;
use App\Models\SurveyFeedback;
use App\Models\SurveyRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkOrderController extends Controller
{
    public function issueFromSurvey(Request $request, SurveyRequest $survey)
    {
        $user = Auth::user();

        // Must be authorized: Reseller owning this survey OR Super Admin
        $isAuthorized = $user->isSuperAdmin() || ($user->isReseller() && $user->reseller?->id === $survey->reseller_id);
        if (! $isAuthorized) {
            abort(403, 'Unauthorized. Only the reseller or administrator who initiated this request can issue the client work order.');
        }

        if ($survey->status !== SurveyRequest::STATUS_MD_APPROVED) {
            return back()->with('error', 'Cannot issue Work Order: Survey feasibility must be approved by MD first.');
        }

        $validated = $request->validate([
            'client_po_no' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string'],
            'deadline_days' => ['nullable', 'integer', 'min:1'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $chosenFeedback = ($survey->selectedFeedback && $survey->selectedFeedback->feasible)
            ? $survey->selectedFeedback
            : ($survey->feedbacks->firstWhere('feasible', true) ?? $survey->feedback);

        if ($chosenFeedback && ! $chosenFeedback->feasible) {
            return back()->with('error', 'Cannot issue Work Order: Selected route is not feasible (ইনফিজিবল লিংক অনুমোদিত নয়)।');
        }

        // Pick assigned transmission engineer or default transmission lead
        $txUser = ! empty($validated['assigned_to'])
            ? User::find($validated['assigned_to'])
            : User::whereHas('role', fn ($q) => $q->where('name', Role::TRANSMISSION))->first();

        $deadlineDays = (int) ($validated['deadline_days'] ?? ($chosenFeedback?->expected_delivery_days ?? 7));
        if ($deadlineDays <= 0) {
            $deadlineDays = 7;
        }
        $woNo = ! empty($validated['client_po_no'])
            ? trim($validated['client_po_no'])
            : ('WO-'.date('Y').'-'.strtoupper(Str::random(6)));

        // Create Work Order
        $workOrder = WorkOrder::create([
            'work_order_no' => $woNo,
            'survey_request_id' => $survey->id,
            'assigned_to' => $txUser?->id,
            'status' => WorkOrder::STATUS_ISSUED,
            'deadline' => now()->addDays($deadlineDays),
            'instructions' => $validated['instructions'] ?? ('Execute fiber laying, splicing, and POP termination via '.($chosenFeedback?->provider ?? 'approved NTTN')),
        ]);

        // Transition status to WORK_ORDER_ISSUED
        $oldStatus = $survey->status;
        $survey->update(['status' => SurveyRequest::STATUS_WORK_ORDER_ISSUED]);

        // After work order is issued, clean up any infeasible feedback records so only the chosen feasible link proceeds
        SurveyFeedback::where('survey_request_id', $survey->id)->where('feasible', false)->delete();

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: 'CLIENT_WORK_ORDER_ISSUED',
            fromStatus: $oldStatus,
            toStatus: SurveyRequest::STATUS_WORK_ORDER_ISSUED,
            remarks: "Client Work Order {$woNo} placed by {$user->name} (".($survey->reseller->company_name ?? 'Reseller').'). Transmission team authorized to begin field jointing & fiber deployment.',
            userId: $user->id
        );

        return redirect()->route('surveys.show', $survey)
            ->with('success', "Work Order {$woNo} issued successfully! Transmission team can now begin on-field jointing and splicing.");
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = WorkOrder::with(['surveyRequest.customer', 'surveyRequest.reseller', 'assignedEngineer', 'nocHandover']);

        if ($user->role->name === Role::TRANSMISSION) {
            $query->where('assigned_to', $user->id);
        }

        if ($user->role->name === Role::RESELLER) {
            $query->whereHas('surveyRequest', fn ($q) => $q->where('reseller_id', $user->reseller?->id ?? 0));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $workOrders = $query->latest()->paginate(15);

        return view('work_orders.index', compact('workOrders'));
    }

    public function startWork(WorkOrder $workOrder)
    {
        $workOrder->update(['status' => WorkOrder::STATUS_WORK_IN_PROGRESS]);
        $workOrder->surveyRequest->update(['status' => SurveyRequest::STATUS_WORK_IN_PROGRESS]);

        ActivityLog::record(
            entityType: 'work_order',
            entityId: $workOrder->id,
            action: 'WORK_IN_PROGRESS_STARTED',
            fromStatus: WorkOrder::STATUS_ISSUED,
            toStatus: WorkOrder::STATUS_WORK_IN_PROGRESS,
            remarks: 'Transmission engineer commenced physical fiber laying and jointing',
            userId: Auth::id()
        );

        return back()->with('success', 'Work order marked as IN PROGRESS.');
    }

    public function handoverToNoc(Request $request, WorkOrder $workOrder)
    {
        $validated = $request->validate([
            'circuit_id' => ['required', 'string', 'max:120'],
            'nttn_link_id' => ['nullable', 'string', 'max:120'],
            'nttn_provider' => ['required', 'string', 'max:120'],
            'vlan' => ['required', 'string', 'max:100'],
            'port' => ['required', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ]);

        $survey = $workOrder->surveyRequest;

        // 1. Mark work order completed
        $workOrder->update([
            'status' => WorkOrder::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // 2. Provision or update draft Link record
        $link = $workOrder->link;
        if (! $link) {
            $linkId = 'LNK-'.date('Y').'-'.strtoupper(Str::random(5));
            $link = Link::create([
                'link_id' => $linkId,
                'customer_id' => $survey->customer_id,
                'reseller_id' => $survey->reseller_id,
                'work_order_id' => $workOrder->id,
                'bandwidth_mbps' => $survey->bandwidth_mbps,
                'nttn_provider' => $validated['nttn_provider'],
                'circuit_id' => $validated['circuit_id'],
                'nttn_link_id' => $validated['nttn_link_id'] ?? null,
                'vlan' => $validated['vlan'],
                'port' => $validated['port'],
                'status' => Link::STATUS_PENDING,
            ]);
        } else {
            $linkId = $link->link_id;
            $link->update([
                'nttn_provider' => $validated['nttn_provider'],
                'circuit_id' => $validated['circuit_id'],
                'nttn_link_id' => $validated['nttn_link_id'] ?? null,
                'vlan' => $validated['vlan'],
                'port' => $validated['port'],
            ]);
        }

        // 3. Create or update NOC Handover record
        $handover = $workOrder->nocHandover;
        $isReHandover = (bool) $handover;
        if (! $handover) {
            $handover = NocHandover::create([
                'work_order_id' => $workOrder->id,
                'link_id' => $link->id,
                'status' => NocHandover::STATUS_RECEIVED,
                'remarks' => $validated['remarks'] ?? 'Physical fiber termination & patch panel ready for NOC verification.',
                'handed_over_at' => now(),
            ]);
        } else {
            $handover->update([
                'status' => NocHandover::STATUS_RECEIVED,
                'remarks' => $validated['remarks'] ?? 'Re-handed over to NOC after field rectification & re-splicing.',
                'handed_over_at' => now(),
                'verified_at' => null,
                'verified_by' => null,
            ]);

            // If there's an open trouble ticket for this link, update it to WORKING
            Ticket::where('link_id', $link->id)
                ->where('status', Ticket::STATUS_OPEN)
                ->update([
                    'status' => Ticket::STATUS_WORKING,
                ]);
        }

        // 4. Update survey status
        $oldStatus = $survey->status;
        $survey->update(['status' => SurveyRequest::STATUS_NOC_HANDOVER]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: $isReHandover ? 'NOC_RE_HANDOVER_SUBMITTED' : 'NOC_HANDOVER_INITIATED',
            fromStatus: $oldStatus,
            toStatus: SurveyRequest::STATUS_NOC_HANDOVER,
            remarks: ($isReHandover ? 'Re-handed over to NOC after field rework' : 'Handed over to NOC by Transmission')." (Circuit: {$validated['circuit_id']}, Link: {$linkId}). Notes: ".($validated['remarks'] ?? 'None'),
            userId: Auth::id()
        );

        return redirect()->route('surveys.show', $survey)
            ->with('success', $isReHandover ? 'Rectified link re-handed over to NOC successfully!' : "Handover to NOC submitted successfully with Link ID {$linkId}!");
    }

    /**
     * Remove the specified work order from storage (Super Admin only).
     */
    public function destroy(WorkOrder $workOrder)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only Super Administrator can permanently delete work orders.');
        }

        $woNo = $workOrder->work_order_no;

        DB::transaction(function () use ($workOrder, $woNo, $user) {
            // Nullify work order reference on links
            Link::where('work_order_id', $workOrder->id)->update(['work_order_id' => null]);

            // Delete NOC handovers & tests
            if ($workOrder->nocHandover) {
                $workOrder->nocHandover->nocTests()->delete();
                $workOrder->nocHandover->delete();
            }

            // Delete activity logs for this work order
            ActivityLog::where('entity_type', 'work_order')
                ->where('entity_id', $workOrder->id)
                ->delete();

            // Record deletion audit
            ActivityLog::record(
                entityType: 'work_order',
                entityId: $workOrder->id,
                action: 'WORK_ORDER_DELETED',
                fromStatus: $workOrder->status,
                toStatus: null,
                remarks: "Super Admin {$user->name} permanently deleted work order {$woNo}.",
                userId: $user->id
            );

            $workOrder->delete();
        });

        return redirect()->route('work-orders.index')
            ->with('success', "Work Order '{$woNo}' has been permanently deleted.");
    }
}
