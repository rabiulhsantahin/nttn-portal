<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Link;
use App\Models\NocHandover;
use App\Models\Role;
use App\Models\SurveyRequest;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    /**
     * Remove the specified link from storage (Super Admin only).
     */
    public function destroy(Link $link)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only Super Administrator can permanently delete links.');
        }

        $linkId = $link->link_id;

        DB::transaction(function () use ($link, $linkId, $user) {
            // Delete associated tickets and ticket messages
            $ticketIds = $link->tickets()->pluck('id');
            if ($ticketIds->isNotEmpty()) {
                TicketMessage::whereIn('ticket_id', $ticketIds)->delete();
                $link->tickets()->delete();
            }

            // Delete chat room and messages
            if ($link->chatRoom) {
                $link->chatRoom->messages()->delete();
                $link->chatRoom->delete();
            }

            // Delete billing and revisions
            if ($link->billing) {
                $link->billing->rateRevisions()->delete();
                $link->billing->delete();
            }
            $link->rateRevisions()->delete();
            $link->serviceRequests()->delete();

            // Clear foreign key from NOC handovers
            NocHandover::where('link_id', $link->id)->update(['link_id' => null]);

            // Clear activity logs for this link
            ActivityLog::where('entity_type', 'link')
                ->where('entity_id', $link->id)
                ->delete();

            // Record deletion audit
            ActivityLog::record(
                entityType: 'link',
                entityId: $link->id,
                action: 'LINK_DELETED',
                fromStatus: $link->status,
                toStatus: null,
                remarks: "Super Admin {$user->name} permanently deleted circuit link {$linkId}.",
                userId: $user->id
            );

            $link->delete();
        });

        return redirect()->route('links.index')
            ->with('success', "Circuit link '{$linkId}' and all associated records have been permanently deleted.");
    }
}
