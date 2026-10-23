<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_noc_user_can_access_chat_for_any_link(): void
    {
        $nocUser = User::where('email', 'noc@nttn.test')->first();
        $link = Link::first();

        $response = $this->actingAs($nocUser)->get(route('links.chat', $link));
        $response->assertOk();
    }

    public function test_reseller_can_access_chat_for_own_link(): void
    {
        $resellerUser = User::where('email', 'reseller@isp.test')->first();
        $link = Link::where('reseller_id', $resellerUser->reseller->id)->first();

        $response = $this->actingAs($resellerUser)->get(route('links.chat', $link));
        $response->assertOk();
    }

    public function test_reseller_and_noc_can_exchange_messages(): void
    {
        Storage::fake('public');
        $resellerUser = User::where('email', 'reseller@isp.test')->first();
        $nocUser = User::where('email', 'noc@nttn.test')->first();
        $link = Link::where('reseller_id', $resellerUser->reseller->id)->first();

        // Reseller sends message with attachment
        $file = UploadedFile::fake()->create('telemetry.log', 100);
        $sendResp = $this->actingAs($resellerUser)->post(route('links.chat.send', $link), [
            'message' => 'NOC team, please verify optical levels for this link.',
            'attachment' => $file,
        ]);
        $sendResp->assertRedirect();

        // Reseller views chat - message and attachment should render cleanly
        $chatResp = $this->actingAs($resellerUser)->get(route('links.chat', $link));
        $chatResp->assertOk();
        $chatResp->assertSee('NOC team, please verify optical levels for this link.');
        $chatResp->assertSee('Attached Technical Telemetry / Trace');

        // NOC views chat
        $nocChatResp = $this->actingAs($nocUser)->get(route('links.chat', $link));
        $nocChatResp->assertOk();
        $nocChatResp->assertSee('NOC team, please verify optical levels for this link.');

        // NOC sends reply via JSON
        $nocReply = $this->actingAs($nocUser)->postJson(route('links.chat.send', $link), [
            'message' => 'Optical level is -16.8 dBm, link is healthy.',
        ]);
        $nocReply->assertOk();
        $nocReply->assertJson(['success' => true]);

        // Reseller polls messages via JSON
        $pollResp = $this->actingAs($resellerUser)->getJson(route('links.chat.messages', $link));
        $pollResp->assertOk();
        $pollResp->assertJsonCount(2);
    }
}
