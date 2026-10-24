<?php

namespace App\Mail;

use App\Models\Link;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class NttnIncidentTroubleTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public Link $link
    ) {}

    public function envelope(): Envelope
    {
        $nttnCompany = $this->ticket->nttn_provider ?: ($this->link->nttn_provider ?? 'NTTN');
        $nttnLinkId = $this->ticket->nttn_link_id ?: ($this->link->nttn_link_id ?: 'N/A');
        $priority = strtoupper($this->ticket->priority ?? 'NORMAL');

        return new Envelope(
            subject: "[FAULT ESCALATION] {$priority} Trouble Ticket #{$this->ticket->ticket_no} | {$nttnCompany} Link ID: {$nttnLinkId}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nttn_trouble_ticket',
            with: [
                'ticket' => $this->ticket,
                'link' => $this->link,
                'appName' => config('app.name', 'NTTN Portal'),
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->ticket->attachment_path && Storage::disk('public')->exists($this->ticket->attachment_path)) {
            $attachments[] = Attachment::fromStorageDisk('public', $this->ticket->attachment_path)
                ->as('incident_evidence_'.basename($this->ticket->attachment_path));
        }

        return $attachments;
    }
}
