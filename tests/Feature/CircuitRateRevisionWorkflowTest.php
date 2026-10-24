<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\Billing;
use App\Models\BillingRateRevision;
use App\Models\Customer;
use App\Models\Link;
use App\Models\Reseller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CircuitRateRevisionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $billingUser;

    private User $mdUser;

    private User $reseller1User;

    private User $reseller2User;

    private Reseller $reseller1;

    private Reseller $reseller2;

    private array $roles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roles = [
            Role::SUPER_ADMIN => Role::create(['name' => Role::SUPER_ADMIN]),
            Role::MD => Role::create(['name' => Role::MD]),
            Role::RESELLER => Role::create(['name' => Role::RESELLER]),
            Role::TRANSMISSION => Role::create(['name' => Role::TRANSMISSION]),
            Role::NOC => Role::create(['name' => Role::NOC]),
            Role::BILLING => Role::create(['name' => Role::BILLING]),
        ];

        $this->billingUser = User::create([
            'role_id' => $this->roles[Role::BILLING]->id,
            'name' => 'Billing Officer',
            'email' => 'billing@test.com',
            'phone' => '+8801700000006',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->mdUser = User::create([
            'role_id' => $this->roles[Role::MD]->id,
            'name' => 'Managing Director',
            'email' => 'md@test.com',
            'phone' => '+8801700000002',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->reseller1User = User::create([
            'role_id' => $this->roles[Role::RESELLER]->id,
            'name' => 'Apex Reseller',
            'email' => 'apex@isp.test',
            'phone' => '+8801700000010',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->reseller1 = Reseller::create([
            'user_id' => $this->reseller1User->id,
            'reseller_code' => 'RSL-APEX',
            'company_name' => 'Apex Fiber Net Ltd.',
            'address' => 'Dhaka',
            'phone' => '+8801700000010',
        ]);

        $this->reseller2User = User::create([
            'role_id' => $this->roles[Role::RESELLER]->id,
            'name' => 'Delta Reseller',
            'email' => 'delta@isp.test',
            'phone' => '+8801700000020',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->reseller2 = Reseller::create([
            'user_id' => $this->reseller2User->id,
            'reseller_code' => 'RSL-DELTA',
            'company_name' => 'Delta Communications Ltd.',
            'address' => 'Chittagong',
            'phone' => '+8801700000020',
        ]);
    }

    private function createActiveCircuit(Reseller $reseller, float $monthlyCharge = 35000.00): Link
    {
        $customer = Customer::create([
            'reseller_id' => $reseller->id,
            'name' => 'Corporate Client',
            'phone' => '+8801811111111',
            'address' => 'Gulshan, Dhaka',
            'district' => 'Dhaka',
            'thana' => 'Gulshan',
        ]);

        $link = Link::create([
            'reseller_id' => $reseller->id,
            'customer_id' => $customer->id,
            'link_id' => 'LNK-TEST-'.rand(1000, 9999),
            'circuit_id' => 'CIR-TEST-'.rand(1000, 9999),
            'bandwidth_mbps' => 200,
            'nttn_provider' => 'Bahon',
            'status' => Link::STATUS_ACTIVE,
            'activation_date' => now()->subMonths(2)->toDateString(),
        ]);

        Billing::create([
            'link_id' => $link->id,
            'installation_charge' => 15000.00,
            'monthly_charge' => $monthlyCharge,
            'nttn_cost' => 10000.00,
            'status' => Billing::STATUS_COMPLETED,
            'invoice_no' => 'INV-TEST-'.rand(1000, 9999),
        ]);

        return $link;
    }

    public function test_billing_staff_can_view_rate_edit_page(): void
    {
        $link = $this->createActiveCircuit($this->reseller1, 35000.00);

        $response = $this->actingAs($this->billingUser)->get(route('billing.links.edit-rate', $link));

        $response->assertStatus(200);
        $response->assertSee('Commercial Rate Revision Desk');
        $response->assertSee($link->circuit_id);
        $response->assertSee('35,000.00');
    }

    public function test_reseller_cannot_access_rate_edit_page(): void
    {
        $link = $this->createActiveCircuit($this->reseller1, 35000.00);

        $response = $this->actingAs($this->reseller1User)->get(route('billing.links.edit-rate', $link));

        $response->assertStatus(403);
    }

    public function test_billing_can_submit_rate_revision_for_md_approval_and_existing_rate_remains_unchanged(): void
    {
        $link = $this->createActiveCircuit($this->reseller1, 35000.00);

        $response = $this->actingAs($this->billingUser)->post(route('billing.links.revise-rate', $link), [
            'monthly_charge' => 28000.00,
            'installation_charge' => 15000.00,
            'nttn_cost' => 8000.00,
            'effective_date' => now()->toDateString(),
            'reason' => 'Annual client discount negotiated for high volume bandwidth.',
        ]);

        $response->assertRedirect(route('billing.index'));
        $response->assertSessionHas('success');

        // Verify BillingRateRevision record exists in PENDING status
        $revision = BillingRateRevision::where('link_id', $link->id)->first();
        $this->assertNotNull($revision);
        $this->assertEquals(BillingRateRevision::STATUS_PENDING, $revision->status);
        $this->assertEquals(35000.00, $revision->old_monthly_charge);
        $this->assertEquals(28000.00, $revision->new_monthly_charge);

        // Verify Approval entry exists
        $approval = Approval::where('entity_type', 'rate_revision')
            ->where('entity_id', $revision->id)
            ->first();
        $this->assertNotNull($approval);
        $this->assertEquals(Approval::STATUS_PENDING, $approval->status);
        $this->assertEquals(Approval::STAGE_MD_RATE_REVISION, $approval->approval_stage);

        // CRITICAL: Billing record's monthly_charge MUST STILL BE 35,000.00 (Unchanged!)
        $this->assertEquals(35000.00, (float) $link->billing->fresh()->monthly_charge);

        // Verify Reseller 1 views their portal and STILL SEES 35,000.00 (Not 28,000.00)
        $resellerView = $this->actingAs($this->reseller1User)->get(route('billing.index'));
        $resellerView->assertStatus(200);
        $resellerView->assertSee('Unit Price');
        $resellerView->assertSee('35,000.00');
        $resellerView->assertSee('175.00'); // 35000 / 200 Mbps
        $resellerView->assertDontSee('140.00'); // pending 28000 / 200 Mbps
        // Reseller must NOT see the pending 28,000.00 rate revision badge or controls
        $resellerView->assertDontSee('Rev. Pending');

        $resellerLinkView = $this->actingAs($this->reseller1User)->get(route('links.show', $link));
        $resellerLinkView->assertStatus(200);
        $resellerLinkView->assertSee('35,000');
        $resellerLinkView->assertDontSee('Edit Rate');
    }

    public function test_md_can_view_pending_rate_revisions_in_approvals_desk(): void
    {
        $link = $this->createActiveCircuit($this->reseller1, 35000.00);

        $this->actingAs($this->billingUser)->post(route('billing.links.revise-rate', $link), [
            'monthly_charge' => 28000.00,
            'reason' => 'Annual client discount negotiated.',
        ]);

        $response = $this->actingAs($this->mdUser)->get(route('approvals.index'));

        $response->assertStatus(200);
        $response->assertSee('Stage 4: Circuit Commercial Rate Revision Approvals');
        $response->assertSee($link->circuit_id);
        $response->assertSee('35,000.00');
        $response->assertSee('28,000.00');
        $response->assertSee('Annual client discount negotiated.');
    }

    public function test_md_approval_applies_new_rate_and_updates_reseller_portal(): void
    {
        $link = $this->createActiveCircuit($this->reseller1, 35000.00);

        $this->actingAs($this->billingUser)->post(route('billing.links.revise-rate', $link), [
            'monthly_charge' => 28000.00,
            'reason' => 'Negotiated client rate revision.',
        ]);

        $revision = BillingRateRevision::where('link_id', $link->id)->first();

        // MD approves the revision
        $decisionResponse = $this->actingAs($this->mdUser)->post(route('approvals.rate-revisions.decide', $revision), [
            'decision' => 'approve',
            'remarks' => 'Approved as discussed in executive committee meeting.',
        ]);

        $decisionResponse->assertRedirect(route('approvals.index'));
        $decisionResponse->assertSessionHas('success');

        // Revision status updated to APPROVED
        $this->assertEquals(BillingRateRevision::STATUS_APPROVED, $revision->fresh()->status);
        $this->assertEquals($this->mdUser->id, $revision->fresh()->approved_by);

        // Billing record updated to 28,000.00
        $this->assertEquals(28000.00, (float) $link->billing->fresh()->monthly_charge);

        // Reseller 1 logs in and visits billing portal: NOW sees 28,000.00 and new unit price 140.00!
        $resellerView = $this->actingAs($this->reseller1User)->get(route('billing.index'));
        $resellerView->assertStatus(200);
        $resellerView->assertSee('28,000.00');
        $resellerView->assertSee('140.00'); // 28000 / 200 Mbps

        // Reseller circuit details view also displays the updated rate
        $resellerLinkView = $this->actingAs($this->reseller1User)->get(route('links.show', $link));
        $resellerLinkView->assertStatus(200);
        $resellerLinkView->assertSee('28,000');
    }

    public function test_md_rejection_leaves_current_rate_unchanged(): void
    {
        $link = $this->createActiveCircuit($this->reseller1, 35000.00);

        $this->actingAs($this->billingUser)->post(route('billing.links.revise-rate', $link), [
            'monthly_charge' => 28000.00,
            'reason' => 'Proposed rate decrease.',
        ]);

        $revision = BillingRateRevision::where('link_id', $link->id)->first();

        // MD rejects the revision
        $decisionResponse = $this->actingAs($this->mdUser)->post(route('approvals.rate-revisions.decide', $revision), [
            'decision' => 'reject',
            'remarks' => 'Margin too low. Maintain current rate.',
        ]);

        $decisionResponse->assertRedirect(route('approvals.index'));
        $decisionResponse->assertSessionHas('warning');

        // Revision marked REJECTED
        $this->assertEquals(BillingRateRevision::STATUS_REJECTED, $revision->fresh()->status);

        // Billing record remains 35,000.00
        $this->assertEquals(35000.00, (float) $link->billing->fresh()->monthly_charge);

        // Reseller continues to see 35,000.00
        $resellerView = $this->actingAs($this->reseller1User)->get(route('billing.index'));
        $resellerView->assertStatus(200);
        $resellerView->assertSee('35,000.00');
        $resellerView->assertDontSee('28,000.00');
    }

    public function test_reseller_cannot_decide_rate_revisions(): void
    {
        $link = $this->createActiveCircuit($this->reseller1, 35000.00);

        $this->actingAs($this->billingUser)->post(route('billing.links.revise-rate', $link), [
            'monthly_charge' => 28000.00,
            'reason' => 'Special promo discount.',
        ]);

        $revision = BillingRateRevision::where('link_id', $link->id)->first();

        $response = $this->actingAs($this->reseller1User)->post(route('approvals.rate-revisions.decide', $revision), [
            'decision' => 'approve',
        ]);

        $response->assertStatus(403);
    }

    public function test_md_can_direct_approve_rate_revision_on_submission(): void
    {
        $link = $this->createActiveCircuit($this->reseller1, 35000.00);

        $response = $this->actingAs($this->mdUser)->post(route('billing.links.revise-rate', $link), [
            'monthly_charge' => 38000.00,
            'reason' => 'Bandwidth upgrade revision directly approved by MD.',
            'direct_approve' => 1,
        ]);

        $response->assertRedirect(route('billing.index'));

        // Billing rate updated directly
        $this->assertEquals(38000.00, (float) $link->billing->fresh()->monthly_charge);

        $revision = BillingRateRevision::where('link_id', $link->id)->first();
        $this->assertEquals(BillingRateRevision::STATUS_APPROVED, $revision->status);
    }

    public function test_billing_can_propose_reseller_and_nttn_carrier_edit_which_remains_pending_until_md_approves(): void
    {
        $link = $this->createActiveCircuit($this->reseller1, 35000.00);
        $this->assertEquals($this->reseller1->id, $link->reseller_id);
        $this->assertEquals('Bahon', $link->nttn_provider);

        // Propose changing rate to 30000, reseller to reseller2, and carrier to Summit Communications
        $response = $this->actingAs($this->billingUser)->post(route('billing.links.revise-rate', $link), [
            'monthly_charge' => 30000.00,
            'reseller_id' => $this->reseller2->id,
            'nttn_provider' => 'Summit Communications',
            'reason' => 'Transferring circuit to Delta reseller on Summit network as per MD memo #77.',
        ]);

        $response->assertRedirect(route('billing.index'));

        // Revision is pending
        $revision = BillingRateRevision::where('link_id', $link->id)->first();
        $this->assertNotNull($revision);
        $this->assertEquals(BillingRateRevision::STATUS_PENDING, $revision->status);
        $this->assertEquals($this->reseller1->id, $revision->old_reseller_id);
        $this->assertEquals($this->reseller2->id, $revision->new_reseller_id);
        $this->assertEquals('Bahon', $revision->old_nttn_provider);
        $this->assertEquals('Summit Communications', $revision->new_nttn_provider);

        // Crucial: Link itself MUST REMAIN UNCHANGED until MD approves!
        $link->refresh();
        $this->assertEquals($this->reseller1->id, $link->reseller_id);
        $this->assertEquals('Bahon', $link->nttn_provider);
        $this->assertEquals(35000.00, (float) $link->billing->monthly_charge);

        // MD views approvals desk and sees proposed reseller and carrier
        $approvalsDesk = $this->actingAs($this->mdUser)->get(route('approvals.index'));
        $approvalsDesk->assertStatus(200);
        $approvalsDesk->assertSee('New Reseller');
        $approvalsDesk->assertSee('Delta Communications Ltd.');
        $approvalsDesk->assertSee('New Carrier');
        $approvalsDesk->assertSee('Summit Communications');

        // MD approves the revision
        $approveResponse = $this->actingAs($this->mdUser)->post(route('approvals.rate-revisions.decide', $revision), [
            'decision' => 'approve',
            'remarks' => 'Approved transfer and rate adjustment.',
        ]);

        $approveResponse->assertRedirect(route('approvals.index'));

        // Verify link is now updated to the new reseller and NTTN carrier
        $link->refresh();
        $this->assertEquals($this->reseller2->id, $link->reseller_id);
        $this->assertEquals('Summit Communications', $link->nttn_provider);
        $this->assertEquals(30000.00, (float) $link->billing->monthly_charge);
    }

    public function test_md_direct_approval_immediately_updates_reseller_and_nttn_carrier(): void
    {
        $link = $this->createActiveCircuit($this->reseller1, 35000.00);

        $response = $this->actingAs($this->mdUser)->post(route('billing.links.revise-rate', $link), [
            'monthly_charge' => 32000.00,
            'reseller_id' => $this->reseller2->id,
            'nttn_provider' => 'Fiber@Home',
            'reason' => 'Direct reseller migration by MD.',
            'direct_approve' => 1,
        ]);

        $response->assertRedirect(route('billing.index'));

        $link->refresh();
        $this->assertEquals($this->reseller2->id, $link->reseller_id);
        $this->assertEquals('Fiber@Home', $link->nttn_provider);
        $this->assertEquals(32000.00, (float) $link->billing->monthly_charge);
    }

    public function test_md_rejection_leaves_reseller_and_nttn_carrier_unchanged(): void
    {
        $link = $this->createActiveCircuit($this->reseller1, 35000.00);

        $this->actingAs($this->billingUser)->post(route('billing.links.revise-rate', $link), [
            'monthly_charge' => 29000.00,
            'reseller_id' => $this->reseller2->id,
            'nttn_provider' => 'Fiber@Home',
            'reason' => 'Transfer request.',
        ]);

        $revision = BillingRateRevision::where('link_id', $link->id)->first();

        // MD rejects
        $this->actingAs($this->mdUser)->post(route('approvals.rate-revisions.decide', $revision), [
            'decision' => 'reject',
            'remarks' => 'Transfer not permitted.',
        ]);

        $link->refresh();
        $this->assertEquals($this->reseller1->id, $link->reseller_id);
        $this->assertEquals('Bahon', $link->nttn_provider);
        $this->assertEquals(35000.00, (float) $link->billing->monthly_charge);
    }
}
