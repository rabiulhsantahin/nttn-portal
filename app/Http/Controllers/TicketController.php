<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Link;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user->role->name;

        $query = Ticket::with(['link.customer', 'link.reseller', 'creator', 'assignedEngineer']);

        if ($role === Role::RESELLER) {
            $query->where('created_by', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->latest()->paginate(15);

        return view('tickets.index', compact('tickets', 'role'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $selectedLink = null;

        if ($request->filled('link_id')) {
            $selectedLink = Link::find($request->link_id);
        }

        $linksQuery = Link::where('status', Link::STATUS_ACTIVE);
        if ($user->role->name === Role::RESELLER) {
            $linksQuery->where('reseller_id', $user->reseller?->id);
        }
        $links = $linksQuery->with('customer')->get();

        return view('tickets.create', compact('links', 'selectedLink'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'link_id' => ['required', 'exists:links,id'],
            'type' => ['required', 'string', 'max:60'],
            'priority' => ['required', 'in:CRITICAL,HIGH,NORMAL,LOW'],
            'description' => ['required', 'string'],
        ]);

        $ticketNo = 'TT-' . date('Y') . '-' . strtoupper(Str::random(5));

        $ticket = Ticket::create([
            'ticket_no' => $ticketNo,
            'link_id' => $validated['link_id'],
            'created_by' => Auth::id(),
            'type' => $validated['type'],
            'priority' => $validated['priority'],
            'description' => $validated['description'],
            'status' => Ticket::STATUS_OPEN,
        ]);

        ActivityLog::record(
            entityType: 'ticket',
            entityId: $ticket->id,
            action: 'TICKET_CREATED',
            fromStatus: null,
            toStatus: Ticket::STATUS_OPEN,
            remarks: "Trouble ticket {$ticketNo} ({$validated['priority']}) logged: {$validated['type']}",
            userId: Auth::id()
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Trouble Ticket {$ticketNo} created successfully!");
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['link.customer', 'link.reseller', 'creator', 'assignedEngineer']);
        $engineers = User::whereHas('role', fn($q) => $q->whereIn('name', [Role::NOC, Role::TRANSMISSION]))->get();

        $activities = ActivityLog::where('entity_type', 'ticket')
            ->where('entity_id', $ticket->id)
            ->with('user')
            ->latest('created_at')
            ->get();

        return view('tickets.show', compact('ticket', 'engineers', 'activities'));
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:OPEN,WORKING,RESOLVED,CLOSED'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'remarks' => ['nullable', 'string'],
        ]);

        $oldStatus = $ticket->status;
        $updates = ['status' => $validated['status']];

        if (array_key_exists('assigned_to', $validated)) {
            $updates['assigned_to'] = $validated['assigned_to'];
        }

        if ($validated['status'] === Ticket::STATUS_RESOLVED && !$ticket->resolved_at) {
            $updates['resolved_at'] = now();
        }

        if ($validated['status'] === Ticket::STATUS_CLOSED && !$ticket->closed_at) {
            $updates['closed_at'] = now();
        }

        $ticket->update($updates);

        ActivityLog::record(
            entityType: 'ticket',
            entityId: $ticket->id,
            action: 'TICKET_STATUS_UPDATED',
            fromStatus: $oldStatus,
            toStatus: $validated['status'],
            remarks: $validated['remarks'] ?? "Ticket marked as {$validated['status']}",
            userId: Auth::id()
        );

        return back()->with('success', "Ticket status updated to {$validated['status']}.");
    }
}
