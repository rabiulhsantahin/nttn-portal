<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\Billing;
use App\Models\Customer;
use App\Models\Link;
use App\Models\Reseller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyBillingWorkflowTest extends TestCase
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

    public function test_billing_user_can_access_legacy_creation_form(): void
    {
        $response = $this->actingAs($this->billingUser)->get(route('billing.legacy.create'));

        $response->assertStatus(200);
        $response->assertSee('Manual Legacy Data Entry Desk');
        $response->assertSee('Apex Fiber Net Ltd.');
    }

    public function test_reseller_cannot_access_legacy_creation_form(): void
    {
        $response = $this->actingAs($this->reseller1User)->get(route('billing.legacy.create'));

        $response->assertStatus(403);
    }

    public function test_billing_can_submit_legacy_data_for_md_approval(): void
    {
        Storage::fake('public');

        $doc = UploadedFile::fake()->create('md_approval_memo.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->billingUser)->post(route('billing.legacy.store'), [
            'reseller_id' => $this->reseller1->id,
            'customer_mode' => 'new',
            'customer_name' => 'Standard Chartered Bank Gulshan Branch',
            'customer_phone' => '+8801711223344',
            'customer_district' => 'Dhaka',
            'customer_thana' => 'Gulshan',
            'circuit_id' => 'CIR-2023-SCB-001',
            'link_id' => 'LNK-LEG-SCB-01',
            'bandwidth_mbps' => 200,
            'nttn_provider' => 'Summit Communications',
            'nttn_link_id' => 'SCL-SCB-9988',
            'vlan' => '2105',
            'port' => 'TenGigE-0/1/1',
            'activation_date' => '2023-06-15',
            'monthly_charge' => 35000.00,
            'installation_charge' => 15000.00,
            'nttn_cost' => 18000.00,
            'invoice_no' => 'INV-LEG-2023-001',
            'md_approval_ref' => 'MEMO/MD/2023/SCB-88',
            'md_approval_doc' => $doc,
            'legacy_notes' => 'Legacy branch link approved by MD under annual bank discount rate.',
        ]);

        $response->assertRedirect(route('billing.index'));
        $response->assertSessionHas('success');

        // Check Link was created with PENDING status
        $this->assertDatabaseHas('links', [
            'link_id' => 'LNK-LEG-SCB-01',
            'circuit_id' => 'CIR-2023-SCB-001',
            'reseller_id' => $this->reseller1->id,
            'is_legacy' => true,
            'status' => Link::STATUS_PENDING,
            'bandwidth_mbps' => 200,
            'md_approval_ref' => 'MEMO/MD/2023/SCB-88',
        ]);

        // Check Customer was created
        $this->assertDatabaseHas('customers', [
            'name' => 'Standard Chartered Bank Gulshan Branch',
            'reseller_id' => $this->reseller1->id,
        ]);

        // Check Billing was created with PENDING status
        $this->assertDatabaseHas('billing', [
            'invoice_no' => 'INV-LEG-2023-001',
            'monthly_charge' => 35000.00,
            'is_legacy' => true,
            'status' => Billing::STATUS_PENDING,
        ]);

        // Check Approval record is queued for MD
        $link = Link::where('link_id', 'LNK-LEG-SCB-01')->firstOrFail();
        $this->assertDatabaseHas('approvals', [
            'entity_type' => 'link',
            'entity_id' => $link->id,
            'approval_stage' => Approval::STAGE_MD_LEGACY_BILLING,
            'status' => Approval::STATUS_PENDING,
        ]);
    }

    public function test_md_can_review_and_approve_legacy_circuit(): void
    {
        // 1. Setup pending legacy link
        $customer = Customer::create([
            'reseller_id' => $this->reseller1->id,
            'name' => 'Grameenphone Banani POP',
            'district' => 'Dhaka',
        ]);

        $link = Link::create([
            'link_id' => 'LNK-LEG-GP-001',
            'circuit_id' => 'CIR-2023-GP-001',
            'customer_id' => $customer->id,
            'reseller_id' => $this->reseller1->id,
            'bandwidth_mbps' => 500,
            'nttn_provider' => 'Fiber@Home',
            'status' => Link::STATUS_PENDING,
            'is_legacy' => true,
            'md_approval_ref' => 'MEMO/MD/2023/GP-10',
            'activation_date' => '2023-04-01',
        ]);

        $billing = Billing::create([
            'link_id' => $link->id,
            'monthly_charge' => 75000.00,
            'installation_charge' => 25000.00,
            'nttn_cost' => 30000.00,
            'invoice_no' => 'INV-LEG-GP-01',
            'status' => Billing::STATUS_PENDING,
            'is_legacy' => true,
        ]);

        Approval::create([
            'entity_type' => 'link',
            'entity_id' => $link->id,
            'approval_stage' => Approval::STAGE_MD_LEGACY_BILLING,
            'status' => Approval::STATUS_PENDING,
        ]);

        // MD views approvals queue
        $approvalsPage = $this->actingAs($this->mdUser)->get(route('approvals.index'));
        $approvalsPage->assertStatus(200);
        $approvalsPage->assertSee('CIR-2023-GP-001');
        $approvalsPage->assertSee('Stage 3: Manual Legacy Circuits');

        // MD submits approval
        $response = $this->actingAs($this->mdUser)->post(route('approvals.legacy.decide', $link), [
            'decision' => 'approve',
            'remarks' => 'Approved by MD. Legacy optical circuit verified.',
        ]);

        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('success');

        // Verify link is now ACTIVE
        $link->refresh();
        $this->assertEquals(Link::STATUS_ACTIVE, $link->status);

        // Verify billing is COMPLETED
        $billing->refresh();
        $this->assertEquals(Billing::STATUS_COMPLETED, $billing->status);
        $this->assertEquals($this->mdUser->id, $billing->completed_by);

        // Verify Approval record is APPROVED
        $this->assertDatabaseHas('approvals', [
            'entity_type' => 'link',
            'entity_id' => $link->id,
            'approval_stage' => Approval::STAGE_MD_LEGACY_BILLING,
            'status' => Approval::STATUS_APPROVED,
            'approved_by' => $this->mdUser->id,
        ]);

        // Verify ChatRoom was initialized
        $this->assertDatabaseHas('chat_rooms', [
            'link_id' => $link->id,
        ]);
    }

    public function test_approved_legacy_data_shows_up_in_respective_reseller_portal_only(): void
    {
        // 1. Create active approved legacy circuit for Reseller 1
        $customer1 = Customer::create([
            'reseller_id' => $this->reseller1->id,
            'name' => 'Robi Axiata Agargaon DataCenter',
            'district' => 'Dhaka',
        ]);

        $link1 = Link::create([
            'link_id' => 'LNK-LEG-ROBI-01',
            'circuit_id' => 'CIR-2023-ROBI-01',
            'customer_id' => $customer1->id,
            'reseller_id' => $this->reseller1->id,
            'bandwidth_mbps' => 1000,
            'nttn_provider' => 'Summit Communications',
            'status' => Link::STATUS_ACTIVE,
            'is_legacy' => true,
            'md_approval_ref' => 'MEMO/MD/ROBI-99',
            'activation_date' => '2023-01-10',
        ]);

        Billing::create([
            'link_id' => $link1->id,
            'monthly_charge' => 120000.00,
            'installation_charge' => 50000.00,
            'nttn_cost' => 60000.00,
            'invoice_no' => 'INV-LEG-ROBI-01',
            'status' => Billing::STATUS_COMPLETED,
            'is_legacy' => true,
        ]);

        // 2. Reseller 1 logs in
        $reseller1Billing = $this->actingAs($this->reseller1User)->get(route('billing.index'));
        $reseller1Billing->assertStatus(200);
        // Reseller 1 MUST see their circuit and billing
        $reseller1Billing->assertSee('CIR-2023-ROBI-01');
        $reseller1Billing->assertSee('Robi Axiata Agargaon DataCenter');
        $reseller1Billing->assertSee('120,000.00');
        $reseller1Billing->assertSee('INV-LEG-ROBI-01');

        $reseller1Links = $this->actingAs($this->reseller1User)->get(route('links.index'));
        $reseller1Links->assertStatus(200);
        $reseller1Links->assertSee('LNK-LEG-ROBI-01');
        $reseller1Links->assertSee('CIR-2023-ROBI-01');

        // 3. Reseller 2 logs in (Delta Reseller)
        $reseller2Billing = $this->actingAs($this->reseller2User)->get(route('billing.index'));
        $reseller2Billing->assertStatus(200);
        // Reseller 2 MUST NOT see Reseller 1's circuit or billing!
        $reseller2Billing->assertDontSee('CIR-2023-ROBI-01');
        $reseller2Billing->assertDontSee('Robi Axiata Agargaon DataCenter');
        $reseller2Billing->assertDontSee('INV-LEG-ROBI-01');

        $reseller2Links = $this->actingAs($this->reseller2User)->get(route('links.index'));
        $reseller2Links->assertStatus(200);
        $reseller2Links->assertDontSee('LNK-LEG-ROBI-01');
    }

    public function test_md_can_directly_approve_and_activate_legacy_circuit(): void
    {
        $response = $this->actingAs($this->mdUser)->post(route('billing.legacy.store'), [
            'reseller_id' => $this->reseller2->id,
            'customer_mode' => 'new',
            'customer_name' => 'Banglalink Regional Hub',
            'circuit_id' => 'CIR-2023-BL-01',
            'link_id' => 'LNK-LEG-BL-01',
            'bandwidth_mbps' => 300,
            'nttn_provider' => 'Bahon Limited',
            'activation_date' => '2023-08-01',
            'monthly_charge' => 45000.00,
            'installation_charge' => 10000.00,
            'direct_approve' => '1',
            'md_approval_ref' => 'MD/EXECUTIVE/DIRECT/01',
        ]);

        $response->assertRedirect(route('billing.index'));

        // Should be directly ACTIVE
        $this->assertDatabaseHas('links', [
            'link_id' => 'LNK-LEG-BL-01',
            'reseller_id' => $this->reseller2->id,
            'status' => Link::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('billing', [
            'monthly_charge' => 45000.00,
            'status' => Billing::STATUS_COMPLETED,
        ]);

        $this->assertDatabaseHas('approvals', [
            'entity_type' => 'link',
            'status' => Approval::STATUS_APPROVED,
            'approved_by' => $this->mdUser->id,
        ]);
    }
}
