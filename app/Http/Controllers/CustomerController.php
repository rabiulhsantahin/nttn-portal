<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Link;
use App\Models\Role;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * List all customers (filtered by reseller for reseller role).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Customer::with(['reseller', 'links', 'surveyRequests'])
            ->withCount(['links', 'surveyRequests']);

        if ($user->isReseller()) {
            $query->where('reseller_id', $user->reseller?->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%")
                    ->orWhere('thana', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(20);

        return view('customers.index', compact('customers'));
    }

    /**
     * Show a single customer profile with full circuit & ticket history.
     */
    public function show(Customer $customer)
    {
        $user = Auth::user();

        // Resellers can only see their own customers
        if ($user->isReseller() && $customer->reseller_id !== $user->reseller?->id) {
            abort(403, 'Access restricted to your own customers.');
        }

        $customer->load([
            'reseller.user',
            'links.billing',
            'links.workOrder.nocHandover',
            'surveyRequests' => fn ($q) => $q->latest()->take(10),
        ]);

        $stats = [
            'active_links' => $customer->links->where('status', Link::STATUS_ACTIVE)->count(),
            'total_bandwidth' => $customer->links->where('status', Link::STATUS_ACTIVE)->sum('bandwidth_mbps'),
            'total_surveys' => $customer->surveyRequests->count(),
            'open_tickets' => Ticket::whereHas('link', fn ($q) => $q->where('customer_id', $customer->id))
                ->whereIn('status', ['OPEN', 'WORKING'])->count(),
        ];

        $openTickets = Ticket::whereHas('link', fn ($q) => $q->where('customer_id', $customer->id))
            ->with('link')
            ->whereIn('status', ['OPEN', 'WORKING'])
            ->latest()
            ->take(5)
            ->get();

        return view('customers.show', compact('customer', 'stats', 'openTickets'));
    }
}
