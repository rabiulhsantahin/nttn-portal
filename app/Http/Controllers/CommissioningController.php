<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\ChatRoom;
use App\Models\Link;
use App\Models\SurveyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommissioningController extends Controller
{
    public function finalApprove(Request $request, SurveyRequest $survey)
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        $link = $survey->workOrder?->link;

        if (! $link) {
            return back()->with('error', 'Associated link not found.');
        }

        // 1. Record Final Approval
        Approval::create([
            'entity_type' => 'survey_request',
            'entity_id' => $survey->id,
            'approval_stage' => Approval::STAGE_FINAL_MD,
            'status' => Approval::STATUS_APPROVED,
            'remarks' => $validated['remarks'] ?? 'Final commercial & technical commissioning approved.',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // 2. Activate Link
        $link->update([
            'status' => Link::STATUS_ACTIVE,
            'activation_date' => now(),
        ]);

        // 3. Initialize dedicated NOC Chat Room for this link
        ChatRoom::firstOrCreate(['link_id' => $link->id]);

        // 4. Update Survey Request status
        $oldStatus = $survey->status;
        $survey->update(['status' => SurveyRequest::STATUS_ACTIVE]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey->id,
            action: 'FINAL_COMMISSIONING_APPROVED',
            fromStatus: $oldStatus,
            toStatus: SurveyRequest::STATUS_ACTIVE,
            remarks: "MD signed off on final commissioning. Link {$link->link_id} is now ACTIVE and live traffic enabled.",
            userId: Auth::id()
        );

        ActivityLog::record(
            entityType: 'link',
            entityId: $link->id,
            action: 'LINK_ACTIVATED',
            fromStatus: Link::STATUS_PENDING,
            toStatus: Link::STATUS_ACTIVE,
            remarks: "Circuit {$link->circuit_id} commissioned into production.",
            userId: Auth::id()
        );

        return redirect()->route('links.show', $link)
            ->with('success', "Link {$link->link_id} is now officially ACTIVE! Live chat room opened.");
    }
}
