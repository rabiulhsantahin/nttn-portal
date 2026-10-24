<?php

namespace Tests\Feature;

use App\Mail\NttnIncidentTroubleTicketMail;
use App\Models\Link;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TroubleTicketNttnEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_ticket_create_page_displays_nttn_provider_and_mail_fields(): void
    {
        $nocUser = User::where('email', 'noc@nttn.test')->first();
        $link = Link::first();

        $response = $this->actingAs($nocUser)->get(route('tickets.create', ['link_id' => $link->id]));

        $response->assertOk();
        $response->assertSee('NTTN Carrier Escalation &amp; NOC Notification', false);
        $response->assertSee('NTTN Provider Company');
        $response->assertSee('NTTN Company Link ID');
        $response->assertSee('NTTN Provider Company Mail Address');
        $response->assertSee('Auto-dispatch fault escalation email', false);
    }

    public function test_creating_ticket_auto_dispatches_email_to_nttn_provider_with_link_id(): void
    {
        Mail::fake();

        $nocUser = User::where('email', 'noc@nttn.test')->first();
        $link = Link::first();

        $ticketData = [
            'link_id' => $link->id,
            'type' => 'Total Optical Loss (Fiber Cut)',
            'priority' => 'CRITICAL',
            'description' => 'Major fiber core sheared near Magura Bypass junction. Urgent optical restoration required.',
            'nttn_provider' => 'Summit Communications',
            'nttn_link_id' => 'SUM-LNK-88201',
            'nttn_provider_email' => 'noc@summitcommunications.net',
            'send_nttn_email' => '1',
        ];

        $response = $this->actingAs($nocUser)->post(route('tickets.store'), $ticketData);

        $ticket = Ticket::where('nttn_link_id', 'SUM-LNK-88201')->first();
        $this->assertNotNull($ticket);
        $this->assertSame('Summit Communications', $ticket->nttn_provider);
        $this->assertSame('noc@summitcommunications.net', $ticket->nttn_provider_email);
        $this->assertNotNull($ticket->nttn_email_sent_at);

        $response->assertRedirect(route('tickets.show', $ticket));
        $response->assertSessionHas('success');

        Mail::assertSent(NttnIncidentTroubleTicketMail::class, function ($mail) use ($ticket) {
            $hasRecipient = $mail->hasTo('noc@summitcommunications.net');
            $subjectMatches = str_contains($mail->envelope()->subject, 'SUM-LNK-88201')
                && str_contains($mail->envelope()->subject, $ticket->ticket_no);

            return $hasRecipient && $subjectMatches;
        });
    }

    public function test_creating_ticket_with_send_nttn_email_unchecked_does_not_dispatch_mail(): void
    {
        Mail::fake();

        $nocUser = User::where('email', 'noc@nttn.test')->first();
        $link = Link::first();

        $ticketData = [
            'link_id' => $link->id,
            'type' => 'Interface Flapping',
            'priority' => 'LOW',
            'description' => 'Internal router testing only, no carrier intervention needed.',
            'nttn_provider' => 'Fiber@Home',
            'nttn_link_id' => 'FAH-LNK-5501',
            'nttn_provider_email' => 'noc@fiberathome.net',
            'send_nttn_email' => '0',
        ];

        $response = $this->actingAs($nocUser)->post(route('tickets.store'), $ticketData);

        $ticket = Ticket::where('nttn_link_id', 'FAH-LNK-5501')->first();
        $this->assertNotNull($ticket);
        $this->assertNull($ticket->nttn_email_sent_at);

        Mail::assertNothingSent();
    }

    public function test_noc_user_can_resend_escalation_email_to_nttn_carrier(): void
    {
        Mail::fake();

        $nocUser = User::where('email', 'noc@nttn.test')->first();
        $link = Link::first();

        $ticket = Ticket::create([
            'ticket_no' => 'TT-2026-TEST1',
            'link_id' => $link->id,
            'created_by' => $nocUser->id,
            'type' => 'Total Optical Loss (Fiber Cut)',
            'priority' => 'HIGH',
            'description' => 'Attenuator fault.',
            'nttn_provider' => 'Bahon Limited',
            'nttn_link_id' => 'BAHON-LINK-772',
            'nttn_provider_email' => 'noc@bahonltd.com',
            'status' => Ticket::STATUS_OPEN,
        ]);

        $response = $this->actingAs($nocUser)->post(route('tickets.resend-nttn-email', $ticket), [
            'email' => 'escalations@bahonltd.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame('escalations@bahonltd.com', $ticket->nttn_provider_email);
        $this->assertNotNull($ticket->nttn_email_sent_at);

        Mail::assertSent(NttnIncidentTroubleTicketMail::class, function ($mail) {
            return $mail->hasTo('escalations@bahonltd.com') && str_contains($mail->envelope()->subject, 'BAHON-LINK-772');
        });
    }
}
