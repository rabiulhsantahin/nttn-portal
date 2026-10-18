<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Link;
use App\Models\NocHandover;
use App\Models\Role;
use App\Models\SurveyRequest;
use App\Models\Ticket;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = $user->role->name;

        // Base metrics
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

        // Role-filtered records
        $recentSurveys = SurveyRequest::with(['customer', 'reseller', 'feedback'])
            ->when($role === Role::RESELLER, function ($query) use ($user) {
                $query->where('reseller_id', $user->reseller?->id);
            })
            ->latest()
            ->take(6)
            ->get();

        $recentLinks = Link::with(['customer', 'reseller'])
            ->when($role === Role::RESELLER, function ($query) use ($user) {
                $query->where('reseller_id', $user->reseller?->id);
            })
            ->latest()
            ->take(5)
            ->get();

        $recentTickets = Ticket::with(['link', 'creator'])
            ->when($role === Role::RESELLER, function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->latest()
            ->take(5)
            ->get();

        $recentActivities = ActivityLog::with('user')
            ->latest('created_at')
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
