<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Link;
use App\Models\NocHandover;
use App\Models\Role;
use App\Models\SurveyRequest;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = WorkOrder::with(['surveyRequest.customer', 'surveyRequest.reseller', 'assignedEngineer', 'nocHandover']);

        if ($user->role->name === Role::TRANSMISSION) {
            $query->where('assigned_to', $user->id);
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

        // 2. Provision draft Link record
        $linkId = 'LNK-' . date('Y') . '-' . strtoupper(Str::random(5));
        $link = Link::create([
            'link_id' => $linkId,
            'customer_id' => $survey->customer_id,
            'reseller_id' => $survey->reseller_id,
            'work_order_id' => $workOrder->id,
            'bandwidth_mbps' => $survey->bandwidth_mbps,
            'nttn_provider' => $validated['nttn_provider'],
            'circuit_id' => $validated['circuit_id'],
            'vlan' => $validated['vlan'],
            'port' => $validated['port'],
            'status' => Link::STATUS_PENDING,
        ]);

        // 3. Create NOC Handover record
        $handover = NocHandover::create([
            'work_order_id' => $workOrder->id,
            'link_id' => $link->id,
            'status' => NocHandover::STATUS_RECEIVED,
            'remarks' => $validated['remarks'] ?? 'Physical fiber termination & patch panel ready for NOC verification.',
            'handed_over_at' => now(),
        ]);

        // 4. Update survey status
        $oldStatus = $survey->status;
        $survey->update(['status' => SurveyRequest::STATUS_NOC_HANDOVER]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: 'NOC_HANDOVER_INITIATED',
            fromStatus: $oldStatus,
            toStatus: SurveyRequest::STATUS_NOC_HANDOVER,
            remarks: "Handed over to NOC by Transmission. Draft Link {$linkId} created (Circuit: {$validated['circuit_id']})",
            userId: Auth::id()
        );

        return redirect()->route('surveys.show', $survey)
            ->with('success', "Handover to NOC submitted successfully with Link ID {$linkId}!");
    }
}
