<?php

namespace App\Http\Controllers;

use App\Mail\NttnIncidentTroubleTicketMail;
use App\Models\ActivityLog;
use App\Models\Link;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user->role->name;

        $query = Ticket::with(['link.customer', 'link.reseller', 'creator', 'assignedEngineer']);

        if ($role === Role::RESELLER) {
            $resellerId = $user->reseller?->id ?? 0;
            $query->where(function ($q) use ($user, $resellerId) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('link', fn ($lq) => $lq->where('reseller_id', $resellerId));
            });
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

    public static function getDefaultNttnEmail(?string $provider): string
    {
        return SystemSetting::getEmailForProvider($provider);
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
        $links = $linksQuery->with(['customer', 'reseller'])->get();

        return view('tickets.create', compact('links', 'selectedLink'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'link_id' => ['required', 'exists:links,id'],
            'type' => ['required', 'string', 'max:60'],
            'priority' => ['required', 'in:CRITICAL,HIGH,NORMAL,LOW'],
            'description' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'], // Max 10MB image
            'nttn_provider' => ['nullable', 'string', 'max:120'],
            'nttn_link_id' => ['nullable', 'string', 'max:120'],
            'nttn_provider_email' => ['nullable', 'string', 'max:255'],
            'send_nttn_email' => ['nullable', 'boolean'],
        ]);

        $link = Link::findOrFail($validated['link_id']);
        $user = Auth::user();
        if ($user->role->name === Role::RESELLER && $link->reseller_id !== $user->reseller?->id) {
            abort(403, 'Unauthorized link selection.');
        }

        $nttnProvider = ! empty($validated['nttn_provider']) ? trim($validated['nttn_provider']) : ($link->nttn_provider ?? 'Summit Communications');
        $nttnLinkId = ! empty($validated['nttn_link_id']) ? trim($validated['nttn_link_id']) : ($link->nttn_link_id ?: 'N/A');
        $nttnEmail = ! empty($validated['nttn_provider_email']) ? trim($validated['nttn_provider_email']) : self::getDefaultNttnEmail($nttnProvider);

        // Update circuit nttn_link_id if not previously set
        if (! empty($validated['nttn_link_id']) && empty($link->nttn_link_id)) {
            $link->update(['nttn_link_id' => $validated['nttn_link_id']]);
        }

        $ticketNo = 'TT-'.date('Y').'-'.strtoupper(Str::random(5));

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('ticket_attachments', 'public');
        }

        $ticket = Ticket::create([
            'ticket_no' => $ticketNo,
            'link_id' => $validated['link_id'],
            'created_by' => Auth::id(),
            'type' => $validated['type'],
            'priority' => $validated['priority'],
            'description' => $validated['description'],
            'nttn_provider' => $nttnProvider,
            'nttn_link_id' => $nttnLinkId,
            'nttn_provider_email' => $nttnEmail,
            'attachment_path' => $attachmentPath,
            'status' => Ticket::STATUS_OPEN,
        ]);

        // Create opening message in ticket thread
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $validated['description'],
            'attachment_path' => $attachmentPath,
        ]);

        // Send automated escalation email to NTTN Provider NOC
        $shouldSendEmail = $request->has('send_nttn_email') ? $request->boolean('send_nttn_email') : true;
        $emailDispatched = false;

        if ($shouldSendEmail && ! empty($nttnEmail)) {
            try {
                $rawRecipients = preg_split('/[,;\s]+/', $nttnEmail, -1, PREG_SPLIT_NO_EMPTY);
                $recipients = array_values(array_filter($rawRecipients, fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));

                if (! empty($recipients)) {
                    Mail::to($recipients)->send(new NttnIncidentTroubleTicketMail($ticket, $link));
                    $ticket->update(['nttn_email_sent_at' => now()]);
                    $emailDispatched = true;

                    TicketMessage::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => Auth::id(),
                        'message' => "Automated NTTN fault escalation dispatched to {$nttnProvider} NOC ({$nttnEmail}) with NTTN Link ID: {$nttnLinkId}.",
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error("Failed to send NTTN trouble ticket email for {$ticketNo}: {$e->getMessage()}");
            }
        }

        ActivityLog::record(
            entityType: 'ticket',
            entityId: $ticket->id,
            action: 'TICKET_CREATED',
            fromStatus: null,
            toStatus: Ticket::STATUS_OPEN,
            remarks: "Trouble ticket {$ticketNo} ({$validated['priority']}) logged: {$validated['type']}".($attachmentPath ? ' [With Image Attachment]' : '').($emailDispatched ? " [Emailed to {$nttnProvider} NOC: {$nttnEmail}]" : ''),
            userId: Auth::id()
        );

        $flashMessage = "Trouble Ticket {$ticketNo} created successfully!";
        if ($emailDispatched) {
            $flashMessage .= " Automated escalation email sent to {$nttnProvider} NOC ({$nttnEmail}) with NTTN Link ID {$nttnLinkId}.";
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', $flashMessage);
    }

    public function resendNttnEmail(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        if ($user->role->name === Role::RESELLER) {
            abort(403, 'Unauthorized.');
        }

        $link = $ticket->link;
        $nttnEmail = $ticket->nttn_provider_email ?: self::getDefaultNttnEmail($ticket->nttn_provider ?: $link->nttn_provider);

        if ($request->filled('email')) {
            $nttnEmail = trim($request->email);
            $ticket->update(['nttn_provider_email' => $nttnEmail]);
        }

        $rawRecipients = preg_split('/[,;\s]+/', $nttnEmail, -1, PREG_SPLIT_NO_EMPTY);
        $recipients = array_values(array_filter($rawRecipients, fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));

        if (empty($recipients)) {
            return back()->with('error', 'No valid email address found to dispatch NTTN ticket.');
        }

        try {
            Mail::to($recipients)->send(new NttnIncidentTroubleTicketMail($ticket, $link));
            $ticket->update(['nttn_email_sent_at' => now()]);

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'message' => "Re-escalation email dispatched to {$ticket->nttn_provider} NOC ({$nttnEmail}).",
            ]);

            return back()->with('success', "Fault escalation email re-sent to {$ticket->nttn_provider} NOC ({$nttnEmail})!");
        } catch (\Throwable $e) {
            Log::error("Failed to re-send NTTN trouble ticket email: {$e->getMessage()}");

            return back()->with('error', 'Failed to dispatch email: '.$e->getMessage());
        }
    }

    public function show(Ticket $ticket)
    {
        $user = Auth::user();
        if ($user->role->name === Role::RESELLER) {
            $resellerId = $user->reseller?->id ?? 0;
            $canAccess = ($ticket->created_by === $user->id) || ($ticket->link && $ticket->link->reseller_id === $resellerId);
            if (! $canAccess) {
                abort(403, 'Unauthorized access to this trouble ticket.');
            }
        }

        $ticket->load([
            'link.customer',
            'link.reseller',
            'creator',
            'assignedEngineer',
            'verifier',
            'messages.user.role',
        ]);

        $engineers = User::whereHas('role', fn ($q) => $q->whereIn('name', [Role::NOC, Role::TRANSMISSION]))->get();

        $activities = ActivityLog::where('entity_type', 'ticket')
            ->where('entity_id', $ticket->id)
            ->with('user')
            ->latest('created_at')
            ->get();

        return view('tickets.show', compact('ticket', 'engineers', 'activities'));
    }

    public function sendMessage(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        if ($user->role->name === Role::RESELLER) {
            $resellerId = $user->reseller?->id ?? 0;
            $canAccess = ($ticket->created_by === $user->id) || ($ticket->link && $ticket->link->reseller_id === $resellerId);
            if (! $canAccess) {
                abort(403, 'Unauthorized access to this trouble ticket.');
            }
        }

        if ($ticket->isLocked()) {
            return back()->with('error', 'This ticket is closed and verified. It is archived in read-only mode.');
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('ticket_attachments', 'public');
        }

        $messageText = $validated['message'] ?? ($attachmentPath ? 'Shared an image.' : '');

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $messageText,
            'attachment_path' => $attachmentPath,
        ]);

        ActivityLog::record(
            entityType: 'ticket',
            entityId: $ticket->id,
            action: 'TICKET_MESSAGE_POSTED',
            fromStatus: $ticket->status,
            toStatus: $ticket->status,
            remarks: Str::limit($messageText, 80).($attachmentPath ? ' [Attached Photo]' : ''),
            userId: Auth::id()
        );

        return back()->with('success', 'Update / picture shared successfully.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        if ($ticket->isLocked()) {
            return back()->with('error', 'This ticket is closed and verified. It is archived in read-only mode and cannot be modified.');
        }

        $user = Auth::user();
        if ($user->role->name === Role::RESELLER) {
            $resellerId = $user->reseller?->id ?? 0;
            $canAccess = ($ticket->created_by === $user->id) || ($ticket->link && $ticket->link->reseller_id === $resellerId);
            if (! $canAccess) {
                abort(403, 'Unauthorized access to this trouble ticket.');
            }
        }

        $isReseller = $user->isReseller();
        $allowedStatuses = $isReseller ? 'OPEN,CLOSED' : 'OPEN,WORKING,RESOLVED,CLOSED';

        $validated = $request->validate([
            'status' => ['required', 'in:'.$allowedStatuses],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'remarks' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $oldStatus = $ticket->status;
        $updates = ['status' => $validated['status']];

        // Reseller is not allowed to re-assign engineers
        if (! $isReseller && array_key_exists('assigned_to', $validated)) {
            $updates['assigned_to'] = $validated['assigned_to'];
        }

        if ($validated['status'] === Ticket::STATUS_RESOLVED && ! $ticket->resolved_at) {
            $updates['resolved_at'] = now();
        }

        if ($validated['status'] === Ticket::STATUS_CLOSED) {
            if (! $ticket->closed_at) {
                $updates['closed_at'] = now();
            }
            // If closed by reseller or customer verify requested, mark verified and lock
            if ($isReseller || Auth::id() === $ticket->created_by || $request->boolean('verify_customer_up')) {
                $updates['is_customer_verified'] = true;
                $updates['verified_at'] = now();
                $updates['verified_by'] = Auth::id();
            }
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('ticket_attachments', 'public');
        }

        $ticket->update($updates);

        // Add update remark to message thread as well
        if ($request->filled('remarks') || $attachmentPath) {
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'message' => "Status updated to {$validated['status']}".($request->filled('remarks') ? ': '.$request->remarks : ''),
                'attachment_path' => $attachmentPath,
            ]);
        }

        ActivityLog::record(
            entityType: 'ticket',
            entityId: $ticket->id,
            action: ($isReseller && $validated['status'] === Ticket::STATUS_CLOSED) ? 'TICKET_CUSTOMER_VERIFIED' : 'TICKET_STATUS_UPDATED',
            fromStatus: $oldStatus,
            toStatus: $validated['status'],
            remarks: ($validated['remarks'] ?? ($isReseller && $validated['status'] === Ticket::STATUS_CLOSED ? 'Customer verified UP and closed ticket.' : "Ticket marked as {$validated['status']}")).($attachmentPath ? ' [Attached Photo]' : ''),
            userId: Auth::id()
        );

        $msg = ($isReseller && $validated['status'] === Ticket::STATUS_CLOSED)
            ? 'Ticket verified and permanently closed.'
            : "Ticket status updated to {$validated['status']}.";

        return back()->with('success', $msg);
    }

    public function verifyCustomerUp(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        if ($user->role->name === Role::RESELLER) {
            $resellerId = $user->reseller?->id ?? 0;
            $canAccess = ($ticket->created_by === $user->id) || ($ticket->link && $ticket->link->reseller_id === $resellerId);
            if (! $canAccess) {
                abort(403, 'Unauthorized access to this trouble ticket.');
            }
        }

        if ($ticket->is_customer_verified) {
            return back()->with('info', 'Ticket is already verified and locked.');
        }

        $ticket->update([
            'status' => Ticket::STATUS_CLOSED,
            'is_customer_verified' => true,
            'verified_at' => now(),
            'verified_by' => Auth::id(),
            'closed_at' => $ticket->closed_at ?? now(),
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => '✅ Customer confirmed connectivity is UP & verified ticket resolution. Ticket permanently closed and locked.',
            'attachment_path' => null,
        ]);

        ActivityLog::record(
            entityType: 'ticket',
            entityId: $ticket->id,
            action: 'TICKET_CUSTOMER_VERIFIED',
            fromStatus: $ticket->status,
            toStatus: Ticket::STATUS_CLOSED,
            remarks: 'Customer confirmed circuit is UP and verified resolution. Ticket permanently closed and archived.',
            userId: Auth::id()
        );

        return back()->with('success', 'Customer verified circuit is UP! Ticket is now permanently closed and archived.');
    }

    /**
     * Remove the specified ticket from storage (Super Admin only).
     */
    public function destroy(Ticket $ticket)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only Super Administrator can permanently delete trouble tickets.');
        }

        $ticketNo = $ticket->ticket_no;

        DB::transaction(function () use ($ticket, $ticketNo, $user) {
            // Remove attachments from disk
            foreach ($ticket->messages as $msg) {
                if ($msg->attachment_path && Storage::disk('public')->exists($msg->attachment_path)) {
                    Storage::disk('public')->delete($msg->attachment_path);
                }
            }

            // Delete messages
            $ticket->messages()->delete();

            // Delete activity logs for this ticket
            ActivityLog::where('entity_type', 'ticket')
                ->where('entity_id', $ticket->id)
                ->delete();

            // Record deletion audit
            ActivityLog::record(
                entityType: 'ticket',
                entityId: $ticket->id,
                action: 'TICKET_DELETED',
                fromStatus: $ticket->status,
                toStatus: null,
                remarks: "Super Admin {$user->name} permanently deleted trouble ticket {$ticketNo}.",
                userId: $user->id
            );

            $ticket->delete();
        });

        return redirect()->route('tickets.index')
            ->with('success', "Trouble ticket '{$ticketNo}' has been permanently deleted.");
    }
}
