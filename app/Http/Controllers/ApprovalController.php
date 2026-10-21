<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\SurveyFeedback;
use App\Models\SurveyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ApprovalController extends Controller
{
    public function index()
    {
        $pendingSurveys = SurveyRequest::where('status', SurveyRequest::STATUS_MD_APPROVAL_PENDING)
            ->whereHas('feedbacks', fn ($q) => $q->where('feasible', true))
            ->with(['customer', 'reseller', 'feedback.creator', 'feedbacks'])
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
            'selected_feedback_id' => [
                'nullable',
                Rule::exists('survey_feedback', 'id')
                    ->where('survey_request_id', $survey->id)
                    ->where('feasible', true),
            ],
            'deadline_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $decision = $validated['decision'];
        $remarks = $validated['remarks'] ?? null;
        $user = Auth::user();

        if ($decision === 'approve') {
            // Select feedback if chosen by MD (only feasible feedback can be approved)
            if (! empty($validated['selected_feedback_id'])) {
                SurveyFeedback::where('survey_request_id', $survey->id)->update(['is_selected' => false]);
                SurveyFeedback::where('survey_request_id', $survey->id)
                    ->where('id', $validated['selected_feedback_id'])
                    ->where('feasible', true)
                    ->update(['is_selected' => true]);
                $survey->unsetRelation('feedback');
                $survey->unsetRelation('feedbacks');
                $survey->unsetRelation('selectedFeedback');
            } else {
                // Ensure at least one feasible feedback is marked is_selected
                $activeFeasible = SurveyFeedback::where('survey_request_id', $survey->id)
                    ->where('feasible', true)
                    ->where('is_selected', true)
                    ->first();
                if (! $activeFeasible) {
                    SurveyFeedback::where('survey_request_id', $survey->id)->update(['is_selected' => false]);
                    SurveyFeedback::where('survey_request_id', $survey->id)
                        ->where('feasible', true)
                        ->orderBy('id', 'asc')
                        ->limit(1)
                        ->update(['is_selected' => true]);
                    $survey->unsetRelation('feedback');
                    $survey->unsetRelation('feedbacks');
                    $survey->unsetRelation('selectedFeedback');
                }
            }

            $chosenFeedback = ($survey->selectedFeedback && $survey->selectedFeedback->feasible)
                ? $survey->selectedFeedback
                : ($survey->feedbacks->firstWhere('feasible', true) ?? $survey->feedback);

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

            // 2. Transition status to MD_APPROVED (Awaiting Client/Reseller Work Order)
            $oldStatus = $survey->status;
            $survey->update(['status' => SurveyRequest::STATUS_MD_APPROVED]);

            ActivityLog::record(
                entityType: 'survey_request',
                entityId: $survey->id,
                action: 'MD_FEASIBILITY_APPROVED',
                fromStatus: $oldStatus,
                toStatus: SurveyRequest::STATUS_MD_APPROVED,
                remarks: 'Approved by MD ('.($chosenFeedback?->provider ?? 'Selected NTTN').'). Commercial feasibility forwarded to Reseller portal for Work Order issuance.',
                userId: $user->id
            );

            return redirect()->route('surveys.show', $survey)
                ->with('success', 'Feasibility approved! The commercial quotation has been forwarded to the Reseller portal for Work Order issuance.');
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
