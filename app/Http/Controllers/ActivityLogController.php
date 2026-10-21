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
}
