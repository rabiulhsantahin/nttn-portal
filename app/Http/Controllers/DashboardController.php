<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Link;
use App\Models\NocHandover;
use App\Models\Role;
use App\Models\ServiceRequest;
use App\Models\SurveyRequest;
use App\Models\Ticket;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Auto-purge any infeasible/rejected surveys or links older than 30 days
        SurveyRequest::pruneExpiredRecords(30);

        $user = Auth::user();
        $role = $user->role->name;
        $isReseller = ($role === Role::RESELLER);
        $resellerId = $user->reseller?->id ?? 0;

        // Metrics scoped by role so resellers only see their own figures
        if ($isReseller) {
            $metrics = [
                'total_surveys' => SurveyRequest::where('reseller_id', $resellerId)->count(),
                'active_links' => Link::where('status', Link::STATUS_ACTIVE)->where('reseller_id', $resellerId)->count(),
                'pending_approvals' => SurveyRequest::where('status', SurveyRequest::STATUS_MD_APPROVAL_PENDING)->where('reseller_id', $resellerId)->count(),
                'final_approvals' => SurveyRequest::where('status', SurveyRequest::STATUS_FINAL_MD_APPROVAL)->where('reseller_id', $resellerId)->count(),
                'open_tickets' => Ticket::whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_WORKING])
                    ->where(function ($q) use ($user, $resellerId) {
                        $q->where('created_by', $user->id)
                            ->orWhereHas('link', fn ($lq) => $lq->where('reseller_id', $resellerId));
                    })->count(),
                'active_work_orders' => WorkOrder::where('status', WorkOrder::STATUS_WORK_IN_PROGRESS)
                    ->whereHas('surveyRequest', fn ($sq) => $sq->where('reseller_id', $resellerId))->count(),
                'noc_handovers_pending' => NocHandover::where('status', NocHandover::STATUS_RECEIVED)
                    ->whereHas('workOrder.surveyRequest', fn ($sq) => $sq->where('reseller_id', $resellerId))->count(),
                'billing_pending' => SurveyRequest::where('status', SurveyRequest::STATUS_BILLING_PENDING)
                    ->where('reseller_id', $resellerId)->count(),
            ];
        } else {
            $metrics = [
                'total_surveys' => SurveyRequest::count(),
                'active_links' => Link::where('status', Link::STATUS_ACTIVE)->count(),
                'pending_approvals' => SurveyRequest::where('status', SurveyRequest::STATUS_MD_APPROVAL_PENDING)->count(),
                'final_approvals' => SurveyRequest::where('status', SurveyRequest::STATUS_FINAL_MD_APPROVAL)->count(),
                'open_tickets' => Ticket::whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_WORKING])->count(),
                'active_work_orders' => WorkOrder::where('status', WorkOrder::STATUS_WORK_IN_PROGRESS)->count(),
                'noc_handovers_pending' => NocHandover::where('status', NocHandover::STATUS_RECEIVED)->count(),
                'billing_pending' => SurveyRequest::where('status', SurveyRequest::STATUS_BILLING_PENDING)->count(),
            ];
        }

        // Role-filtered records
        $recentSurveys = SurveyRequest::with(['customer', 'reseller', 'feedback'])
            ->when($isReseller, function ($query) use ($resellerId) {
                $query->where('reseller_id', $resellerId);
            })
            ->latest()
            ->take(6)
            ->get();

        $recentLinks = Link::with(['customer', 'reseller'])
            ->when($isReseller, function ($query) use ($resellerId) {
                $query->where('reseller_id', $resellerId);
            })
            ->latest()
            ->take(5)
            ->get();

        $recentTickets = Ticket::with(['link', 'creator'])
            ->when($isReseller, function ($query) use ($user, $resellerId) {
                $query->where(function ($q) use ($user, $resellerId) {
                    $q->where('created_by', $user->id)
                        ->orWhereHas('link', fn ($lq) => $lq->where('reseller_id', $resellerId));
                });
            })
            ->latest()
            ->take(5)
            ->get();

        // Scope activity feed so one reseller never sees another reseller's actions/logs
        $activitiesQuery = ActivityLog::with('user');
        if ($isReseller) {
            $activitiesQuery->where(function ($q) use ($resellerId, $user) {
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

        $recentActivities = $activitiesQuery->latest('created_at')
            ->take(8)
            ->get();

        return view('dashboard.index', compact(
            'metrics',
            'recentSurveys',
            'recentLinks',
            'recentTickets',
            'recentActivities',
            'role'
        ));
    }
}
