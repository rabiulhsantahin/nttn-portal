<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\Billing;
use App\Models\BillingRateRevision;
use App\Models\ChatRoom;
use App\Models\Link;
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

        $pendingLegacy = Link::with(['customer', 'reseller', 'billing', 'approvals'])
            ->where('is_legacy', true)
            ->where('status', Link::STATUS_PENDING)
            ->latest()
            ->paginate(10);

        $pendingRateRevisions = BillingRateRevision::with(['link.customer', 'link.reseller', 'billing', 'requester'])
            ->where('status', BillingRateRevision::STATUS_PENDING)
            ->latest()
            ->paginate(10);

        return view('approvals.index', compact('pendingSurveys', 'pendingCommissioning', 'pendingLegacy', 'pendingRateRevisions'));
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

    public function decideLegacy(Request $request, Link $link)
    {
        if (! $link->is_legacy || $link->status !== Link::STATUS_PENDING) {
            return back()->with('error', 'This legacy circuit is not awaiting MD approval.');
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'remarks' => ['nullable', 'string'],
        ]);

        $user = Auth::user();
        $decision = $validated['decision'];
        $remarks = $validated['remarks'] ?? null;

        if ($decision === 'approve') {
            $link->update([
                'status' => Link::STATUS_ACTIVE,
                'activation_date' => $link->activation_date ?? now(),
            ]);

            if ($link->billing) {
                $link->billing->update([
                    'status' => Billing::STATUS_COMPLETED,
                    'completed_by' => $user->id,
                ]);
            }

            ChatRoom::firstOrCreate(['link_id' => $link->id]);

            Approval::updateOrCreate(
                [
                    'entity_type' => 'link',
                    'entity_id' => $link->id,
                    'approval_stage' => Approval::STAGE_MD_LEGACY_BILLING,
                ],
                [
                    'status' => Approval::STATUS_APPROVED,
                    'remarks' => $remarks ?? 'Approved by MD. Circuit and commercial billing ledger active.',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]
            );

            ActivityLog::record(
                entityType: 'link',
                entityId: $link->id,
                action: 'MD_LEGACY_APPROVED',
                fromStatus: Link::STATUS_PENDING,
                toStatus: Link::STATUS_ACTIVE,
                remarks: "MD signed off on manual legacy circuit {$link->circuit_id} ({$link->link_id}). Live in {$link->reseller->company_name}'s reseller portal.",
                userId: $user->id
            );

            return redirect()->route('approvals.index')
                ->with('success', "Legacy Circuit {$link->circuit_id} approved! It is now live in {$link->reseller->company_name}'s reseller portal.");
        }

        if ($decision === 'reject') {
            $link->update(['status' => Link::STATUS_TERMINATED]);

            if ($link->billing) {
                $link->billing->update(['status' => 'REJECTED']);
            }

            Approval::updateOrCreate(
                [
                    'entity_type' => 'link',
                    'entity_id' => $link->id,
                    'approval_stage' => Approval::STAGE_MD_LEGACY_BILLING,
                ],
                [
                    'status' => Approval::STATUS_REJECTED,
                    'remarks' => $remarks ?? 'Rejected by MD.',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]
            );

            ActivityLog::record(
                entityType: 'link',
                entityId: $link->id,
                action: 'MD_LEGACY_REJECTED',
                fromStatus: Link::STATUS_PENDING,
                toStatus: Link::STATUS_TERMINATED,
                remarks: "MD rejected manual legacy circuit {$link->link_id}. Reason: {$remarks}",
                userId: $user->id
            );

            return redirect()->route('approvals.index')
                ->with('warning', "Legacy circuit {$link->link_id} was rejected.");
        }
    }

    public function decideRateRevision(Request $request, BillingRateRevision $revision)
    {
        if (! $revision->isPending()) {
            return back()->with('error', 'This rate revision is not awaiting MD decision.');
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'remarks' => ['nullable', 'string'],
        ]);

        $user = Auth::user();
        $decision = $validated['decision'];
        $remarks = $validated['remarks'] ?? null;
        $link = $revision->link;
        $circuitCode = $link->circuit_id ?: $link->link_id;

        if ($decision === 'approve') {
            $revision->update([
                'status' => BillingRateRevision::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'remarks' => $remarks ?? 'Approved by MD. Rate updated in billing ledger and reseller portal.',
            ]);

            $revision->billing->update([
                'monthly_charge' => $revision->new_monthly_charge,
                'installation_charge' => $revision->new_installation_charge,
                'nttn_cost' => $revision->new_nttn_cost,
                'completed_by' => $user->id,
            ]);

            Approval::updateOrCreate(
                [
                    'entity_type' => 'rate_revision',
                    'entity_id' => $revision->id,
                    'approval_stage' => Approval::STAGE_MD_RATE_REVISION,
                ],
                [
                    'status' => Approval::STATUS_APPROVED,
                    'remarks' => $remarks ?? 'Approved by MD. Commercial monthly rate revised.',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]
            );

            ActivityLog::record(
                entityType: 'link',
                entityId: $link->id,
                action: 'RATE_REVISION_APPROVED_BY_MD',
                fromStatus: null,
                toStatus: null,
                remarks: "MD approved rate revision for {$circuitCode}: ৳ ".number_format($revision->old_monthly_charge, 2).' → ৳ '.number_format($revision->new_monthly_charge, 2).". Live in {$link->reseller->company_name}'s reseller portal.",
                userId: $user->id
            );

            return redirect()->route('approvals.index')
                ->with('success', "Rate revision for Circuit {$circuitCode} approved! The new rate (৳ ".number_format($revision->new_monthly_charge, 2).") is now active in {$link->reseller->company_name}'s portal.");
        }

        if ($decision === 'reject') {
            $revision->update([
                'status' => BillingRateRevision::STATUS_REJECTED,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'remarks' => $remarks ?? 'Rejected by MD.',
            ]);

            Approval::updateOrCreate(
                [
                    'entity_type' => 'rate_revision',
                    'entity_id' => $revision->id,
                    'approval_stage' => Approval::STAGE_MD_RATE_REVISION,
                ],
                [
                    'status' => Approval::STATUS_REJECTED,
                    'remarks' => $remarks ?? 'Rejected by MD.',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]
            );

            ActivityLog::record(
                entityType: 'link',
                entityId: $link->id,
                action: 'RATE_REVISION_REJECTED_BY_MD',
                fromStatus: null,
                toStatus: null,
                remarks: "MD rejected proposed rate revision for Circuit {$circuitCode}. Current rate remains ৳ ".number_format($revision->old_monthly_charge, 2).". Reason: {$remarks}",
                userId: $user->id
            );

            return redirect()->route('approvals.index')
                ->with('warning', "Rate revision request for Circuit {$circuitCode} was rejected. Current billing rate remains unchanged.");
        }
    }
}
