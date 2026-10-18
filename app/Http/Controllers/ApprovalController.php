<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\Role;
use App\Models\SurveyRequest;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ApprovalController extends Controller
{
    public function index()
    {
        $pendingSurveys = SurveyRequest::where('status', SurveyRequest::STATUS_MD_APPROVAL_PENDING)
            ->with(['customer', 'reseller', 'feedback.creator'])
            ->latest()
            ->paginate(10);

        $pendingCommissioning = SurveyRequest::where('status', SurveyRequest::STATUS_FINAL_MD_APPROVAL)
            ->with(['customer', 'reseller', 'workOrder.link.billing'])
            ->latest()
            ->paginate(10);

        return view('approvals.index', compact('pendingSurveys', 'pendingCommissioning'));
    }

    public function decide(Request $request, SurveyRequest $survey)
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject,send_back'],
            'remarks' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'deadline_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $decision = $validated['decision'];
        $remarks = $validated['remarks'] ?? null;
        $user = Auth::user();

        if ($decision === 'approve') {
            // 1. Log approval
            Approval::create([
                'entity_type' => 'survey_request',
                'entity_id' => $survey->id,
                'approval_stage' => Approval::STAGE_MD_SURVEY,
                'status' => Approval::STATUS_APPROVED,
                'remarks' => $remarks,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            // 2. Pick assigned engineer or default transmission user
            $txUser = !empty($validated['assigned_to'])
                ? User::find($validated['assigned_to'])
                : User::whereHas('role', fn($q) => $q->where('name', Role::TRANSMISSION))->first();

            $deadlineDays = $validated['deadline_days'] ?? ($survey->feedback?->expected_delivery_days ?? 7);
            $woNo = 'WO-' . date('Y') . '-' . strtoupper(Str::random(6));

            // 3. Create Work Order
            $workOrder = WorkOrder::create([
                'work_order_no' => $woNo,
                'survey_request_id' => $survey->id,
                'assigned_to' => $txUser?->id,
                'status' => WorkOrder::STATUS_ISSUED,
                'deadline' => now()->addDays($deadlineDays),
                'instructions' => $remarks ?? "Execute fiber laying, splicing, and POP termination as per approved feasibility route.",
            ]);

            // 4. Update survey status
            $oldStatus = $survey->status;
            $survey->update(['status' => SurveyRequest::STATUS_WORK_ORDER_ISSUED]);

            ActivityLog::record(
                entityType: 'survey_request',
                entityId: $survey->id,
                action: 'MD_APPROVED_WORK_ORDER_ISSUED',
                fromStatus: $oldStatus,
                toStatus: SurveyRequest::STATUS_WORK_ORDER_ISSUED,
                remarks: "Approved by MD. Work Order {$woNo} issued to " . ($txUser ? $txUser->name : 'Transmission Team'),
                userId: $user->id
            );

            return redirect()->route('surveys.show', $survey)
                ->with('success', "Survey approved! Work Order {$woNo} generated successfully.");
        }

        if ($decision === 'reject') {
            Approval::create([
                'entity_type' => 'survey_request',
                'entity_id' => $survey->id,
                'approval_stage' => Approval::STAGE_MD_SURVEY,
                'status' => Approval::STATUS_REJECTED,
                'remarks' => $remarks,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $oldStatus = $survey->status;
            $survey->update(['status' => SurveyRequest::STATUS_MD_REJECTED]);

            ActivityLog::record(
                entityType: 'survey_request',
                entityId: $survey->id,
                action: 'MD_REJECTED',
                fromStatus: $oldStatus,
                toStatus: SurveyRequest::STATUS_MD_REJECTED,
                remarks: "Rejected by MD. Reason: {$remarks}",
                userId: $user->id
            );

            return redirect()->route('surveys.show', $survey)
                ->with('warning', 'Survey request rejected.');
        }

        if ($decision === 'send_back') {
            Approval::create([
                'entity_type' => 'survey_request',
                'entity_id' => $survey->id,
                'approval_stage' => Approval::STAGE_MD_SURVEY,
                'status' => Approval::STATUS_SENT_BACK,
                'remarks' => $remarks,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $oldStatus = $survey->status;
            $survey->update(['status' => SurveyRequest::STATUS_SENT_BACK]);

            ActivityLog::record(
                entityType: 'survey_request',
                entityId: $survey->id,
                action: 'MD_SENT_BACK',
                fromStatus: $oldStatus,
                toStatus: SurveyRequest::STATUS_SENT_BACK,
                remarks: "Sent back to Transmission for reassessment. Note: {$remarks}",
                userId: $user->id
            );

            return redirect()->route('surveys.show', $survey)
                ->with('info', 'Survey request returned to Transmission team for review.');
        }
    }
}
