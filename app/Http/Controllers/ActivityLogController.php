<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Link;
use App\Models\ServiceRequest;
use App\Models\SurveyRequest;
use App\Models\Ticket;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ActivityLog::with('user.role');

        if ($user->isReseller()) {
            $resellerId = $user->reseller?->id ?? 0;
            $query->where(function ($q) use ($resellerId, $user) {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($sub) use ($resellerId) {
                        $sub->where('entity_type', 'survey_request')
                            ->whereIn('entity_id', SurveyRequest::where('reseller_id', $resellerId)->select('id'));
                    })
                    ->orWhere(function ($sub) use ($resellerId) {
                        $sub->where('entity_type', 'work_order')
                            ->whereIn('entity_id', WorkOrder::whereHas('surveyRequest', fn ($sq) => $sq->where('reseller_id', $resellerId))->select('id'));
                    })
                    ->orWhere(function ($sub) use ($resellerId) {
                        $sub->where('entity_type', 'link')
                            ->whereIn('entity_id', Link::where('reseller_id', $resellerId)->select('id'));
                    })
                    ->orWhere(function ($sub) use ($resellerId) {
                        $sub->where('entity_type', 'ticket')
                            ->whereIn('entity_id', Ticket::whereHas('link', fn ($lq) => $lq->where('reseller_id', $resellerId))->select('id'));
                    })
                    ->orWhere(function ($sub) use ($resellerId) {
                        $sub->where('entity_type', 'service_request')
                            ->whereIn('entity_id', ServiceRequest::whereHas('link', fn ($lq) => $lq->where('reseller_id', $resellerId))->select('id'));
                    });
            });
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        $logs = $query->latest('created_at')->paginate(25)->withQueryString();

        return view('activity_logs.index', compact('logs'));
    }

    /**
     * Delete an individual activity log (Super Admin only).
     */
    public function destroy(ActivityLog $activityLog)
    {
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only Super Administrator can delete activity logs.');
        }

        $activityLog->delete();

        return back()->with('success', 'Activity log entry has been deleted.');
    }

    /**
     * Purge all activity logs older than a given date, or clear all (Super Admin only).
     */
    public function purge(Request $request)
    {
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only Super Administrator can purge activity logs.');
        }

        $days = $request->integer('days', 0);
        if ($days > 0) {
            $count = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();

            return back()->with('success', "Purged {$count} activity log entries older than {$days} days.");
        }

        $count = ActivityLog::count();
        ActivityLog::truncate();

        ActivityLog::record(
            entityType: 'system',
            entityId: 0,
            action: 'ACTIVITY_LOGS_PURGED',
            fromStatus: null,
            toStatus: null,
            remarks: "Super Admin purged {$count} previous audit log entries.",
            userId: Auth::id()
        );

        return back()->with('success', "All previous activity logs ({$count} entries) have been purged.");
    }
}
