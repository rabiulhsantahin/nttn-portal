<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Role;
use App\Models\SurveyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LinkController extends Controller
{
    public function index(Request $request)
    {
        SurveyRequest::pruneExpiredRecords(30);

        $user = Auth::user();
        $role = $user->role->name;

        $query = Link::with(['customer', 'reseller', 'billing', 'tickets']);

        if ($role === Role::RESELLER) {
            $query->where('reseller_id', $user->reseller?->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $isReseller = ($role === Role::RESELLER);
            $query->where(function ($q) use ($search, $isReseller) {
                $q->where('link_id', 'like', "%{$search}%")
                    ->orWhere('circuit_id', 'like', "%{$search}%")
                    ->when(! $isReseller, function ($sub) use ($search) {
                        $sub->orWhere('nttn_link_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $links = $query->latest()->paginate(15);

        return view('links.index', compact('links', 'role'));
    }

    public function show(Link $link)
    {
        $user = Auth::user();

        // Access check for reseller
        if ($user->role->name === Role::RESELLER && $link->reseller_id !== $user->reseller?->id) {
            abort(403, 'Unauthorized access to this link.');
        }

        $link->load([
            'customer',
            'reseller',
            'workOrder.surveyRequest',
            'billing.completedByUser',
            'tickets.creator',
            'tickets.assignedEngineer',
            'serviceRequests.creator',
            'chatRoom.messages.sender.role',
        ]);

        return view('links.show', compact('link'));
    }
}
