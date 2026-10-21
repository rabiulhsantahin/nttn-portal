<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Reseller;
use App\Models\SurveyRequest;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResellerController extends Controller
{
    /**
     * List all resellers (admin/md view) or show own profile (reseller view).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isReseller()) {
            return redirect()->route('resellers.show', $user->reseller->id);
        }

        $resellers = Reseller::with(['user', 'customers', 'links'])
            ->withCount(['customers', 'links', 'surveyRequests'])
            ->latest()
            ->paginate(20);

        return view('resellers.index', compact('resellers'));
    }

    /**
     * Show the reseller profile with all related data.
     */
    public function show(Reseller $reseller)
    {
        $user = Auth::user();

        // Resellers can only see their own profile
        if ($user->isReseller() && $user->reseller?->id !== $reseller->id) {
            abort(403, 'You can only view your own reseller profile.');
        }

        $reseller->load(['user', 'customers.links', 'links.billing']);

        // Stats
        $stats = [
            'total_customers' => $reseller->customers()->count(),
            'active_links' => $reseller->links()->where('status', Link::STATUS_ACTIVE)->count(),
            'total_bandwidth' => $reseller->links()->where('status', Link::STATUS_ACTIVE)->sum('bandwidth_mbps'),
            'total_surveys' => $reseller->surveyRequests()->count(),
            'pending_surveys' => $reseller->surveyRequests()->whereNotIn('status', [SurveyRequest::STATUS_ACTIVE, 'REJECTED', 'REJECTED_FINAL'])->count(),
            'open_tickets' => Ticket::whereHas('link', fn ($q) => $q->where('reseller_id', $reseller->id))
                ->whereIn('status', ['OPEN', 'WORKING'])->count(),
        ];

        // Recent surveys
        $recentSurveys = $reseller->surveyRequests()
            ->with('customer')
            ->latest()
            ->take(10)
            ->get();

        // All active links
        $activeLinks = $reseller->links()
            ->with(['customer', 'billing'])
            ->where('status', Link::STATUS_ACTIVE)
            ->latest()
            ->get();

        return view('resellers.show', compact('reseller', 'stats', 'recentSurveys', 'activeLinks'));
    }
}
