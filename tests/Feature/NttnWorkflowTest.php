<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\Billing;
use App\Models\Customer;
use App\Models\Link;
use App\Models\Reseller;
use App\Models\Role;
use App\Models\ServiceRequest;
use App\Models\SurveyRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NttnWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_lifecycle_from_survey_to_active_link_and_trouble_ticketing(): void
    {
        // 1. Seed Roles
        $roles = [
            Role::SUPER_ADMIN => Role::create(['name' => Role::SUPER_ADMIN]),
            Role::MD => Role::create(['name' => Role::MD]),
            Role::RESELLER => Role::create(['name' => Role::RESELLER]),
            Role::TRANSMISSION => Role::create(['name' => Role::TRANSMISSION]),
            Role::NOC => Role::create(['name' => Role::NOC]),
            Role::BILLING => Role::create(['name' => Role::BILLING]),
        ];

        // 2. Create Users for each role
        $resellerUser = User::create([
            'role_id' => $roles[Role::RESELLER]->id,
            'name' => 'Apex ISP User',
            'email' => 'reseller@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $reseller = Reseller::create([
            'user_id' => $resellerUser->id,
            'reseller_code' => 'RSL-TEST',
            'company_name' => 'Apex ISP Ltd',
            'phone' => '01711111111',
        ]);

        $txUser = User::create([
            'role_id' => $roles[Role::TRANSMISSION]->id,
            'name' => 'Tx Field Lead',
            'email' => 'tx@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $mdUser = User::create([
            'role_id' => $roles[Role::MD]->id,
            'name' => 'Managing Director',
            'email' => 'md@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $nocUser = User::create([
            'role_id' => $roles[Role::NOC]->id,
            'name' => 'NOC Lead',
            'email' => 'noc@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $billingUser = User::create([
            'role_id' => $roles[Role::BILLING]->id,
            'name' => 'Billing Mgr',
            'email' => 'billing@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 3. STEP 1: Reseller lodges Survey Request
        $this->actingAs($resellerUser);
        $surveyResponse = $this->post(route('surveys.store'), [
            'customer_name' => 'Test Bank Annex',
            'customer_phone' => '01899999999',
            'customer_address' => 'Motijheel C/A',
            'district' => 'Dhaka',
            'thana' => 'Motijheel',
            'bandwidth_mbps' => 300,
            'service_type' => 'Data Link',
            'preferred_nttn' => 'Fiber@Home',
        ]);
        $surveyResponse->assertRedirect();

        $survey = SurveyRequest::first();
        $this->assertNotNull($survey);
        $this->assertEquals(SurveyRequest::STATUS_SURVEY_REQUESTED, $survey->status);
        $this->assertEquals(300, $survey->bandwidth_mbps);

        // 4. STEP 2: Transmission Team submits Feasibility
        $this->actingAs($txUser);
        $fbResponse = $this->post(route('surveys.feedback', $survey), [
            'provider' => 'Fiber@Home',
            'feasible' => 1,
            'route' => 'Motijheel -> Press Club',
            'fiber_distance_km' => 5.250,
            'nearest_pop' => 'Motijheel Central',
            'port' => 'TenGigE-0/1',
            'vlan' => 'VLAN-1002',
            'installation_cost' => 30000,
            'monthly_cost' => 50000,
            'expected_delivery_days' => 5,
            'feedback' => 'Clear path with duct capacity',
        ]);
        $fbResponse->assertRedirect();
        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_MD_APPROVAL_PENDING, $survey->status);
        $this->assertNotNull($survey->feedback);

        // 5. STEP 3: MD Approves Feasibility Quotation
        $this->actingAs($mdUser);
        $apprResponse = $this->post(route('approvals.decide', $survey), [
            'decision' => 'approve',
            'remarks' => 'Approved as per business rate',
            'deadline_days' => 5,
        ]);
        $apprResponse->assertRedirect();
        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_MD_APPROVED, $survey->status);
        $this->assertNull($survey->workOrder);

        // 5.1 STEP 3.5: Reseller places Client Work Order from their portal
        $this->actingAs($resellerUser);
        $woResponse = $this->post(route('surveys.issue-work-order', $survey), [
            'client_po_no' => 'PO-TEST-001',
            'deadline_days' => '5',
            'instructions' => 'Proceed with field jointing and splicing',
        ]);
        $woResponse->assertRedirect();
        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_WORK_ORDER_ISSUED, $survey->status);
        $this->assertNotNull($survey->workOrder);
        $this->assertEquals('PO-TEST-001', $survey->workOrder->work_order_no);

        // 6. STEP 4: Transmission executes field work and hands over to NOC
        $this->actingAs($txUser);
        $hoResponse = $this->post(route('work-orders.handover', $survey->workOrder), [
            'circuit_id' => 'CIR-DH-MOT-001',
            'nttn_provider' => 'Fiber@Home',
            'vlan' => 'VLAN-1002',
            'port' => 'TenGigE-0/1',
            'remarks' => 'Splicing complete, optical level -16.2 dBm',
        ]);
        $hoResponse->assertRedirect();
        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_NOC_HANDOVER, $survey->status);
        $this->assertNotNull($survey->workOrder->nocHandover);
        $this->assertNotNull($survey->workOrder->link);

        // 7. STEP 5: NOC conducts 8-Point SLA test and verifies
        $this->actingAs($nocUser);
        $nocResponse = $this->post(route('noc.submit-test', $survey->workOrder->nocHandover), [
            'physical_link' => '1',
            'optical_power' => '1',
            'vlan_ok' => '1',
            'bandwidth_ok' => '1',
            'ping_ok' => '1',
            'latency_ok' => '1',
            'packet_loss_ok' => '1',
            'monitoring_ok' => '1',
            'remarks' => 'All 8 SLA criteria verified optimal',
        ]);
        $nocResponse->assertRedirect();
        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_BILLING_PENDING, $survey->status);

        // 8. STEP 6: Billing issues commercial invoice
        $this->actingAs($billingUser);
        $billResponse = $this->post(route('billing.process', $survey), [
            'installation_charge' => 30000,
            'monthly_charge' => 50000,
            'nttn_cost' => 20000,
            'billing_start_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(15)->format('Y-m-d'),
        ]);
        $billResponse->assertRedirect();
        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_FINAL_MD_APPROVAL, $survey->status);
        $this->assertNotNull($survey->workOrder->link->billing);

        // 9. STEP 7: MD signs off on Final Commissioning
        $this->actingAs($mdUser);
        $finalResponse = $this->post(route('approvals.final-commission', $survey), [
            'remarks' => 'Final sign-off granted. Live traffic authorized.',
        ]);
        $finalResponse->assertRedirect();
        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_ACTIVE, $survey->status);
        $link = $survey->workOrder->link;
        $link->refresh();
        $this->assertEquals(Link::STATUS_ACTIVE, $link->status);
        $this->assertNotNull($link->chatRoom);

        // 10. STEP 8: Trouble Ticket Logging & Resolution
        $this->actingAs($resellerUser);
        $ticketResponse = $this->post(route('tickets.store'), [
            'link_id' => $link->id,
            'type' => 'Packet Drop & Jitter',
            'priority' => 'HIGH',
            'description' => 'Packet drops observed during peak usage',
        ]);
        $ticketResponse->assertRedirect();
        $ticket = Ticket::first();
        $this->assertEquals(Ticket::STATUS_OPEN, $ticket->status);

        $this->actingAs($nocUser);
        $this->post(route('tickets.update-status', $ticket), [
            'status' => 'RESOLVED',
            'remarks' => 'Corrected optical power jumper',
        ]);
        $ticket->refresh();
        $this->assertEquals(Ticket::STATUS_RESOLVED, $ticket->status);

        // 11. STEP 9: Bandwidth Upgrade 4-Stage Workflow (Billing -> MD -> TX -> Finalized)
        $this->actingAs($resellerUser);
        $this->post(route('links.service-requests.store', $link), [
            'type' => 'UPGRADE',
            'requested_bandwidth_mbps' => 500,
            'reason' => 'Corporate bandwidth expansion',
        ]);
        $sr = ServiceRequest::first();
        // Stage 1: Sent to Billing
        $this->assertEquals(ServiceRequest::STATUS_PENDING_BILLING, $sr->status);

        // Stage 2: Billing sets commercial charge and approves to MD
        $this->actingAs($billingUser);
        $this->post(route('service-requests.decide', $sr), [
            'action' => 'billing_approve',
            'new_monthly_charge' => 75000,
        ]);
        $sr->refresh();
        $this->assertEquals(ServiceRequest::STATUS_PENDING_MD, $sr->status);

        // Stage 3: MD Approves to TX
        $this->actingAs($mdUser);
        $this->post(route('service-requests.decide', $sr), ['action' => 'md_approve']);
        $sr->refresh();
        $this->assertEquals(ServiceRequest::STATUS_PENDING_TX, $sr->status);

        // Stage 4: Transmission / NOC Implements with NTTN & Finalizes Bill
        $this->actingAs($nocUser);
        $this->post(route('service-requests.decide', $sr), [
            'action' => 'tx_implement',
            'tx_remarks' => 'NTTN Summit port policer updated to 500 Mbps',
        ]);
        $sr->refresh();
        $link->refresh();
        $this->assertEquals(ServiceRequest::STATUS_COMPLETED, $sr->status);
        $this->assertEquals(500, $link->bandwidth_mbps);
        $this->assertEquals(75000, (int) $link->billing->monthly_charge);

        // 12. STEP 10: Verify Activity Audit Logs
        $logsCount = ActivityLog::count();
        $this->assertGreaterThan(7, $logsCount);
    }

    public function test_dual_nttn_feasibility_and_md_selection(): void
    {
        $roleMd = Role::create(['name' => Role::MD]);
        $roleTx = Role::create(['name' => Role::TRANSMISSION]);
        $roleReseller = Role::create(['name' => Role::RESELLER]);

        $resellerUser = User::create([
            'role_id' => $roleReseller->id,
            'name' => 'Reseller User',
            'email' => 'reseller2@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $reseller = Reseller::create([
            'user_id' => $resellerUser->id,
            'reseller_code' => 'RSL-02',
            'company_name' => 'Test Net',
            'phone' => '01700000000',
        ]);

        $txUser = User::create([
            'role_id' => $roleTx->id,
            'name' => 'Tx Engineer',
            'email' => 'tx2@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $mdUser = User::create([
            'role_id' => $roleMd->id,
            'name' => 'MD User',
            'email' => 'md2@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->actingAs($resellerUser);
        $this->post(route('surveys.store'), [
            'customer_name' => 'Branch B',
            'customer_phone' => '01900000000',
            'customer_address' => 'Banani, Dhaka',
            'district' => 'Dhaka',
            'thana' => 'Banani',
            'bandwidth_mbps' => 100,
            'service_type' => 'Data Link',
        ]);

        $survey = SurveyRequest::latest()->first();

        // Transmission submits 2 NTTN providers with POP coordinates
        $this->actingAs($txUser);
        $resp = $this->post(route('surveys.feedback', $survey), [
            'provider' => 'Fiber@Home',
            'feasible' => 1,
            'route' => 'Banani Road 11',
            'fiber_distance_km' => 3.2,
            'nearest_pop' => 'Banani POP 1',
            'pop_latitude' => 23.7937,
            'pop_longitude' => 90.4043,
            'installation_cost' => 25000,
            'monthly_cost' => 30000,
            'expected_delivery_days' => 5,
            // 2nd NTTN
            'has_second_nttn' => '1',
            'provider_2' => 'Summit Communications',
            'feasible_2' => 1,
            'route_2' => 'Kemal Ataturk Ave',
            'fiber_distance_km_2' => 2.5,
            'nearest_pop_2' => 'Summit Banani Hub',
            'pop_latitude_2' => 23.7940,
            'pop_longitude_2' => 90.4060,
            'installation_cost_2' => 20000,
            'monthly_cost_2' => 28000,
            'expected_delivery_days_2' => 4,
        ]);
        $resp->assertRedirect();

        $survey->refresh();
        $this->assertCount(2, $survey->feedbacks);
        $secondFeedback = $survey->feedbacks->where('provider', 'Summit Communications')->first();
        $this->assertNotNull($secondFeedback);
        $this->assertEquals(23.7940, $secondFeedback->pop_latitude);

        // MD selects the 2nd provider (Summit) and approves
        $this->actingAs($mdUser);
        $approveResp = $this->post(route('approvals.decide', $survey), [
            'decision' => 'approve',
            'selected_feedback_id' => $secondFeedback->id,
            'remarks' => 'Selecting Summit due to lower distance and better delivery time',
            'deadline_days' => 4,
        ]);
        $approveResp->assertRedirect();
        $approveResp->assertSessionHas('success');

        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_MD_APPROVED, $survey->status);
        $this->assertNull($survey->workOrder);
        $this->assertEquals($secondFeedback->id, $survey->selectedFeedback->id);
        $this->assertEquals('Summit Communications', $survey->selectedFeedback->provider);

        // Reseller receives quotation in portal and issues Work Order
        $this->actingAs($resellerUser);
        $woResp = $this->post(route('surveys.issue-work-order', $survey), [
            'client_po_no' => 'PO-SUMMIT-01',
            'deadline_days' => '4',
            'instructions' => 'Authorized to start field jointing via Summit',
        ]);
        $woResp->assertRedirect();

        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_WORK_ORDER_ISSUED, $survey->status);
        $this->assertNotNull($survey->workOrder);
        $this->assertEquals('PO-SUMMIT-01', $survey->workOrder->work_order_no);
        $this->assertEquals($secondFeedback->id, $survey->selectedFeedback->id);
        $this->assertEquals('Summit Communications', $survey->selectedFeedback->provider);
    }

    public function test_noc_handover_rejection_dispatches_tt_to_tx_and_tx_re_handovers(): void
    {
        $roleMd = Role::create(['name' => Role::MD]);
        $roleTx = Role::create(['name' => Role::TRANSMISSION]);
        $roleReseller = Role::create(['name' => Role::RESELLER]);
        $roleNoc = Role::create(['name' => Role::NOC]);

        $resellerUser = User::create([
            'role_id' => $roleReseller->id,
            'name' => 'Reseller 3',
            'email' => 'reseller3@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $reseller = Reseller::create([
            'user_id' => $resellerUser->id,
            'reseller_code' => 'RSL-03',
            'company_name' => 'City Link Ltd',
            'phone' => '01700000003',
        ]);

        $txUser = User::create([
            'role_id' => $roleTx->id,
            'name' => 'Tx Field Lead 3',
            'email' => 'tx3@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $mdUser = User::create([
            'role_id' => $roleMd->id,
            'name' => 'MD User 3',
            'email' => 'md3@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $nocUser = User::create([
            'role_id' => $roleNoc->id,
            'name' => 'NOC Engineer 3',
            'email' => 'noc3@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 1. Reseller lodges survey
        $this->actingAs($resellerUser);
        $this->post(route('surveys.store'), [
            'customer_name' => 'Test Corp',
            'customer_phone' => '01799999999',
            'customer_address' => 'Gulshan 2, Dhaka',
            'bandwidth_mbps' => 200,
            'service_type' => 'Data Link',
        ]);
        $survey = SurveyRequest::latest()->first();

        // 2. Transmission submits feasibility
        $this->actingAs($txUser);
        $this->post(route('surveys.feedback', $survey), [
            'provider' => 'Fiber@Home',
            'feasible' => 1,
            'route' => 'Gulshan 2 -> Hub',
            'fiber_distance_km' => 2.0,
            'nearest_pop' => 'Gulshan Central POP',
            'installation_cost' => 15000,
            'monthly_cost' => 25000,
            'expected_delivery_days' => 3,
        ]);

        // 3. MD Approves
        $this->actingAs($mdUser);
        $this->post(route('approvals.decide', $survey), [
            'decision' => 'approve',
            'remarks' => 'Approved',
        ]);

        // 4. Reseller issues Work Order
        $this->actingAs($resellerUser);
        $this->post(route('surveys.issue-work-order', $survey), [
            'client_po_no' => 'PO-CITY-01',
            'deadline_days' => '3',
            'assigned_to' => $txUser->id,
        ]);
        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_WORK_ORDER_ISSUED, $survey->status);

        // 5. Transmission hands over to NOC
        $this->actingAs($txUser);
        $this->post(route('work-orders.handover', $survey->workOrder), [
            'circuit_id' => 'CIR-GUL-01',
            'nttn_provider' => 'Fiber@Home',
            'vlan' => 'VLAN-3010',
            'port' => 'TenGigE-0/1/1',
            'remarks' => 'First attempt handover',
        ]);
        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_NOC_HANDOVER, $survey->status);
        $handover = $survey->workOrder->nocHandover;
        $this->assertNotNull($handover);

        // 6. NOC performs SLA testing, detects optical loss, and returns TT back to Tx
        $this->actingAs($nocUser);
        $rejectResp = $this->post(route('noc.submit-test', $handover), [
            'action' => 'reject',
            'physical_link' => '1',
            'optical_power' => '0', // FAILED
            'vlan_ok' => '1',
            'bandwidth_ok' => '0', // FAILED
            'ping_ok' => '0',
            'latency_ok' => '1',
            'packet_loss_ok' => '0',
            'monitoring_ok' => '1',
            'remarks' => 'High optical attenuation (-29 dBm) at Joint 2. Re-splicing required.',
        ]);
        $rejectResp->assertRedirect();
        $rejectResp->assertSessionHas('warning');

        $survey->refresh();
        $handover->refresh();
        $workOrder = $survey->workOrder;
        $workOrder->refresh();

        $this->assertEquals(SurveyRequest::STATUS_WORK_IN_PROGRESS, $survey->status);
        $this->assertEquals(WorkOrder::STATUS_WORK_IN_PROGRESS, $workOrder->status);
        $this->assertEquals('REJECTED', $handover->status);

        // Verify Trouble Ticket was generated and assigned to Tx
        $ticket = Ticket::where('link_id', $handover->link_id)->first();
        $this->assertNotNull($ticket);
        $this->assertEquals(Ticket::STATUS_OPEN, $ticket->status);
        $this->assertEquals($txUser->id, $ticket->assigned_to);
        $this->assertStringContainsString('TT-', $ticket->ticket_no);
        $this->assertStringContainsString('Re-splicing required', $ticket->description);

        // 7. Transmission rectifies on field and re-submits handover
        $this->actingAs($txUser);
        $reHandoverResp = $this->post(route('work-orders.handover', $workOrder), [
            'circuit_id' => 'CIR-GUL-01',
            'nttn_provider' => 'Fiber@Home',
            'vlan' => 'VLAN-3010',
            'port' => 'TenGigE-0/1/1',
            'remarks' => 'Re-spliced Joint 2. Optical power now optimal at -16.8 dBm.',
        ]);
        $reHandoverResp->assertRedirect();
        $reHandoverResp->assertSessionHas('success');

        $survey->refresh();
        $handover->refresh();
        $this->assertEquals(SurveyRequest::STATUS_NOC_HANDOVER, $survey->status);
        $this->assertEquals('RECEIVED', $handover->status);

        // 8. NOC verifies the re-handover with all 8 checks passing
        $this->actingAs($nocUser);
        $verifyResp = $this->post(route('noc.submit-test', $handover), [
            'action' => 'verify',
            'physical_link' => '1',
            'optical_power' => '1',
            'vlan_ok' => '1',
            'bandwidth_ok' => '1',
            'ping_ok' => '1',
            'latency_ok' => '1',
            'packet_loss_ok' => '1',
            'monitoring_ok' => '1',
            'remarks' => 'All 8 SLA criteria verified optimal after re-splicing.',
        ]);
        $verifyResp->assertRedirect();
        $verifyResp->assertSessionHas('success');

        $survey->refresh();
        $handover->refresh();
        $ticket->refresh();

        $this->assertEquals(SurveyRequest::STATUS_BILLING_PENDING, $survey->status);
        $this->assertEquals('VERIFIED', $handover->status);
        $this->assertEquals(Ticket::STATUS_RESOLVED, $ticket->status);
    }

    public function test_link_discontinue_enforces_three_months_lock_in_and_allows_decommissioning(): void
    {
        $roleMd = Role::create(['name' => Role::MD]);
        $roleNoc = Role::create(['name' => Role::NOC]);
        $roleReseller = Role::create(['name' => Role::RESELLER]);

        $resellerUser = User::create([
            'role_id' => $roleReseller->id,
            'name' => 'Reseller 4',
            'email' => 'reseller4@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $reseller = Reseller::create([
            'user_id' => $resellerUser->id,
            'reseller_code' => 'RSL-04',
            'company_name' => 'Delta Net',
            'phone' => '01700000004',
        ]);

        $mdUser = User::create([
            'role_id' => $roleMd->id,
            'name' => 'MD User 4',
            'email' => 'md4@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $nocUser = User::create([
            'role_id' => $roleNoc->id,
            'name' => 'NOC User 4',
            'email' => 'noc4@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'reseller_id' => $reseller->id,
            'name' => 'Delta Customer',
            'phone' => '01711223344',
            'address' => 'Dhanmondi, Dhaka',
        ]);

        // 1. Create a link activated just 1 month ago (within 3-month lock-in)
        $link = Link::create([
            'link_id' => 'LNK-LOCKIN-01',
            'customer_id' => $customer->id,
            'reseller_id' => $reseller->id,
            'bandwidth_mbps' => 100,
            'nttn_provider' => 'Fiber@Home',
            'circuit_id' => 'CIR-LOCKIN-01',
            'vlan' => 'VLAN-100',
            'port' => 'Port-1',
            'status' => Link::STATUS_ACTIVE,
            'activation_date' => now()->subMonth(),
        ]);

        $this->assertFalse($link->canBeDiscontinued());

        // Attempting to discontinue within 3 months must fail
        $this->actingAs($resellerUser);
        $failResp = $this->post(route('links.service-requests.store', $link), [
            'type' => 'DISCONTINUE',
            'reason' => 'Want to cancel early',
        ]);
        $failResp->assertSessionHas('error');
        $this->assertDatabaseMissing('service_requests', [
            'link_id' => $link->id,
            'type' => 'DISCONTINUE',
        ]);

        // 2. Now simulate link activated 4 months ago (past 3-month lock-in)
        $link->update(['activation_date' => now()->subMonths(4)]);
        $this->assertTrue($link->canBeDiscontinued());

        // Submitting discontinue now succeeds
        $successResp = $this->post(route('links.service-requests.store', $link), [
            'type' => 'DISCONTINUE',
            'reason' => 'Branch closing after 4 months of operation',
        ]);
        $successResp->assertRedirect();
        $successResp->assertSessionHas('success');

        $sr = ServiceRequest::where('link_id', $link->id)->where('type', 'DISCONTINUE')->first();
        $this->assertNotNull($sr);
        $this->assertEquals(0, $sr->requested_bandwidth_mbps);
        $this->assertEquals(ServiceRequest::STATUS_PENDING, $sr->status);

        // 3. MD Approves the Discontinuation
        $this->actingAs($mdUser);
        $this->post(route('service-requests.decide', $sr), ['action' => 'approve']);
        $sr->refresh();
        $this->assertEquals(ServiceRequest::STATUS_APPROVED, $sr->status);

        // 4. NOC Implements De-commissioning
        $this->actingAs($nocUser);
        $this->post(route('service-requests.decide', $sr), ['action' => 'implement']);
        $sr->refresh();
        $link->refresh();

        $this->assertEquals(ServiceRequest::STATUS_COMPLETED, $sr->status);
        $this->assertEquals(Link::STATUS_TERMINATED, $link->status);
        $this->assertEquals(0, $link->bandwidth_mbps);
    }

    public function test_survey_infeasible_returns_to_reseller_for_edit_or_cancel(): void
    {
        $roleTx = Role::create(['name' => Role::TRANSMISSION]);
        $roleReseller = Role::create(['name' => Role::RESELLER]);

        $resellerUser = User::create([
            'role_id' => $roleReseller->id,
            'name' => 'Reseller 5',
            'email' => 'reseller5@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $reseller = Reseller::create([
            'user_id' => $resellerUser->id,
            'reseller_code' => 'RSL-05',
            'company_name' => 'Echo Networks',
            'phone' => '01700000005',
        ]);

        $txUser = User::create([
            'role_id' => $roleTx->id,
            'name' => 'Tx Engineer 5',
            'email' => 'tx5@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 1. Reseller creates a survey request
        $this->actingAs($resellerUser);
        $createResp = $this->post(route('surveys.store'), [
            'customer_name' => 'Remote Client Office',
            'customer_phone' => '01911223344',
            'customer_address' => 'Deep Suburb, Savar',
            'district' => 'Dhaka',
            'thana' => 'Savar',
            'latitude' => 23.8500000,
            'longitude' => 90.2600000,
            'bandwidth_mbps' => 50,
            'service_type' => 'Data Link',
            'preferred_nttn' => 'Fiber@Home',
            'description' => 'Need link at factory unit',
        ]);
        $createResp->assertRedirect();

        $survey = SurveyRequest::where('reseller_id', $reseller->id)->latest()->first();
        $this->assertNotNull($survey);
        $this->assertEquals(SurveyRequest::STATUS_SURVEY_REQUESTED, $survey->status);

        // 2. Tx marks as NOT FEASIBLE (feasible = 0)
        $this->actingAs($txUser);
        $feedbackResp = $this->post(route('surveys.feedback', $survey), [
            'provider' => 'Fiber@Home',
            'feasible' => 0, // Not feasible
            'fiber_distance_km' => 12.5,
            'nearest_pop' => 'Savar Core POP',
            'pop_latitude' => 23.8400000,
            'pop_longitude' => 90.2500000,
            'installation_cost' => 80000,
            'monthly_cost' => 20000,
            'expected_delivery_days' => 15,
            'feedback' => 'Duct blocked across highway, no optical reach currently',
        ]);
        $feedbackResp->assertRedirect(route('surveys.show', $survey));
        $feedbackResp->assertSessionHas('warning');

        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_NOT_FEASIBLE, $survey->status);

        // 3. Reseller views the edit page
        $this->actingAs($resellerUser);
        $editResp = $this->get(route('surveys.edit', $survey));
        $editResp->assertOk();
        $editResp->assertSee('Duct blocked across highway');

        // 4. Reseller updates coordinates and changes preferred NTTN
        $updateResp = $this->put(route('surveys.update', $survey), [
            'customer_name' => 'Remote Client Office - Main Gate',
            'customer_phone' => '01911223344',
            'customer_address' => 'Main Highway Road, Savar',
            'district' => 'Dhaka',
            'thana' => 'Savar',
            'latitude' => 23.8450000,
            'longitude' => 90.2550000,
            'bandwidth_mbps' => 100,
            'service_type' => 'Data Link',
            'preferred_nttn' => 'Summit Communications',
            'description' => 'Shifted endpoint to main gate with clear duct access',
        ]);
        $updateResp->assertRedirect(route('surveys.show', $survey));
        $updateResp->assertSessionHas('success');

        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_SURVEY_REQUESTED, $survey->status);
        $this->assertEquals(100, $survey->bandwidth_mbps);
        $this->assertEquals('Summit Communications', $survey->preferred_nttn);
        $this->assertEquals('Remote Client Office - Main Gate', $survey->customer->name);

        // 5. Tx re-surveys and marks as FEASIBLE (feasible = 1)
        $this->actingAs($txUser);
        $resurveyResp = $this->post(route('surveys.feedback', $survey), [
            'provider' => 'Summit Communications',
            'feasible' => 1,
            'fiber_distance_km' => 2.5,
            'nearest_pop' => 'Savar Highway POP',
            'pop_latitude' => 23.8440000,
            'pop_longitude' => 90.2540000,
            'installation_cost' => 25000,
            'monthly_cost' => 15000,
            'expected_delivery_days' => 5,
            'feedback' => 'Direct clear duct available from Highway POP',
        ]);
        $resurveyResp->assertRedirect(route('surveys.show', $survey));
        $resurveyResp->assertSessionHas('success');

        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_MD_APPROVAL_PENDING, $survey->status);

        // 6. Test cancellation by reseller on another infeasible survey
        $survey2 = SurveyRequest::create([
            'request_no' => 'SRV-TEST-CANCEL',
            'reseller_id' => $reseller->id,
            'customer_id' => $survey->customer_id,
            'bandwidth_mbps' => 20,
            'service_type' => 'Data Link',
            'status' => SurveyRequest::STATUS_NOT_FEASIBLE,
            'requested_at' => now(),
        ]);

        $this->actingAs($resellerUser);
        $cancelResp = $this->post(route('surveys.cancel', $survey2), [
            'reason' => 'Customer cancelled order due to duct blockage',
        ]);
        $cancelResp->assertRedirect(route('surveys.show', $survey2));
        $cancelResp->assertSessionHas('info');

        $survey2->refresh();
        $this->assertEquals(SurveyRequest::STATUS_CANCELLED, $survey2->status);
    }

    public function test_nttn_link_id_can_be_saved_and_is_hidden_from_reseller(): void
    {
        $this->seed();

        $txUser = User::whereHas('role', fn ($q) => $q->where('name', Role::TRANSMISSION))->first();
        $resellerUser = User::whereHas('role', fn ($q) => $q->where('name', Role::RESELLER))->first();
        $nocUser = User::whereHas('role', fn ($q) => $q->where('name', Role::NOC))->first();

        $link = Link::where('link_id', 'LNK-CTG-0042')->first();
        $this->assertNotNull($link);

        // Update with custom nttn_link_id
        $link->update([
            'nttn_link_id' => 'SECRET-NTTN-998877',
        ]);

        // 1. NOC user can see NTTN Link ID on links.show and links.index
        $this->actingAs($nocUser);
        $nocView = $this->get(route('links.show', $link));
        $nocView->assertOk();
        $nocView->assertSee('SECRET-NTTN-998877');

        $nocIndex = $this->get(route('links.index'));
        $nocIndex->assertOk();
        $nocIndex->assertSee('SECRET-NTTN-998877');

        // 2. Reseller user MUST NOT see NTTN Link ID on links.show or links.index
        $this->actingAs($resellerUser);
        $resellerView = $this->get(route('links.show', $link));
        $resellerView->assertOk();
        $resellerView->assertDontSee('SECRET-NTTN-998877');

        $resellerIndex = $this->get(route('links.index'));
        $resellerIndex->assertOk();
        $resellerIndex->assertDontSee('SECRET-NTTN-998877');
    }

    public function test_md_can_return_billing_to_billing_department_for_correction(): void
    {
        $this->seed();

        $mdUser = User::whereHas('role', fn ($q) => $q->where('name', Role::MD))->first();
        $billingUser = User::whereHas('role', fn ($q) => $q->where('name', Role::BILLING))->first();
        $reseller = Reseller::first();
        $customer = Customer::first();

        // Create survey at BILLING_PENDING stage with work order and link
        $survey = SurveyRequest::create([
            'request_no' => 'SRV-TEST-BILLING-REVERT',
            'reseller_id' => $reseller->id,
            'customer_id' => $customer->id,
            'bandwidth_mbps' => 100,
            'service_type' => 'Dark Fiber',
            'status' => SurveyRequest::STATUS_BILLING_PENDING,
            'requested_at' => now(),
        ]);

        $wo = WorkOrder::create([
            'work_order_no' => 'WO-TEST-BILLING-REV',
            'survey_request_id' => $survey->id,
            'status' => WorkOrder::STATUS_COMPLETED,
        ]);

        $link = Link::create([
            'link_id' => 'LNK-TEST-BILLING-REV',
            'customer_id' => $customer->id,
            'reseller_id' => $reseller->id,
            'work_order_id' => $wo->id,
            'bandwidth_mbps' => 100,
            'nttn_provider' => 'Fiber@Home',
            'circuit_id' => 'CIR-BILLING-REV-01',
            'status' => Link::STATUS_PENDING,
        ]);

        $this->actingAs($billingUser);
        $billResp = $this->post(route('billing.process', $survey), [
            'installation_charge' => 40000,
            'monthly_charge' => 99999, // Incorrect rate
            'nttn_cost' => 20000,
            'billing_start_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(15)->format('Y-m-d'),
        ]);

        $billResp->assertRedirect();
        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_FINAL_MD_APPROVAL, $survey->status);
        $this->assertEquals(99999, (int) $survey->workOrder->link->billing->monthly_charge);

        // 1. MD returns billing with remarks
        $this->actingAs($mdUser);
        $returnResp = $this->post(route('approvals.return-billing', $survey), [
            'remarks' => 'Monthly rate is incorrect. Negotiated monthly rate is 55000.',
        ]);
        $returnResp->assertRedirect(route('surveys.show', $survey));
        $returnResp->assertSessionHas('warning');

        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_BILLING_PENDING, $survey->status);
        $this->assertEquals('RETURNED', $survey->workOrder->link->billing->status);

        // Verify sent-back approval record was created
        $approval = $survey->approvals()
            ->where('approval_stage', Approval::STAGE_FINAL_MD)
            ->where('status', Approval::STATUS_SENT_BACK)
            ->first();
        $this->assertNotNull($approval);
        $this->assertEquals('Monthly rate is incorrect. Negotiated monthly rate is 55000.', $approval->remarks);

        // 2. Billing logs in, sees the remarks, and corrects the monthly rate
        $this->actingAs($billingUser);
        $billingView = $this->get(route('surveys.show', $survey));
        $billingView->assertOk();
        $billingView->assertSee('Monthly rate is incorrect. Negotiated monthly rate is 55000.');

        $correctResp = $this->post(route('billing.process', $survey), [
            'installation_charge' => 40000,
            'monthly_charge' => 55000, // Corrected rate
            'nttn_cost' => 20000,
            'billing_start_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(15)->format('Y-m-d'),
        ]);
        $correctResp->assertRedirect(route('surveys.show', $survey));

        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_FINAL_MD_APPROVAL, $survey->status);
        $this->assertEquals(55000, (int) $survey->workOrder->link->billing->monthly_charge);
        $this->assertEquals('COMPLETED', $survey->workOrder->link->billing->status);

        // 3. MD reviews and signs off final approval
        $this->actingAs($mdUser);
        $mdFinalResp = $this->post(route('approvals.final-commission', $survey), [
            'remarks' => 'Corrected billing confirmed. Commission approved.',
        ]);
        $mdFinalResp->assertRedirect(route('links.show', $link));

        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_ACTIVE, $survey->status);
        $this->assertEquals(Link::STATUS_ACTIVE, $survey->workOrder->link->status);
    }

    public function test_bandwidth_scaling_pipeline_billing_md_tx_and_final_bill(): void
    {
        $this->seed();

        $resellerUser = User::whereHas('role', fn ($q) => $q->where('name', Role::RESELLER))->first();
        $billingUser = User::whereHas('role', fn ($q) => $q->where('name', Role::BILLING))->first();
        $mdUser = User::whereHas('role', fn ($q) => $q->where('name', Role::MD))->first();
        $txUser = User::whereHas('role', fn ($q) => $q->where('name', Role::TRANSMISSION))->first();

        $link = Link::where('link_id', 'LNK-CTG-0042')->first();
        $this->assertNotNull($link);

        // Ensure link has baseline billing
        Billing::updateOrCreate(
            ['link_id' => $link->id],
            [
                'installation_charge' => 20000,
                'monthly_charge' => 30000,
                'nttn_cost' => 15000,
                'billing_start_date' => now()->subDays(7),
                'due_date' => now()->addDays(20),
                'invoice_no' => 'INV-2026-BASELINE',
                'status' => Billing::STATUS_COMPLETED,
            ]
        );

        // 1. Reseller submits Bandwidth Upgrade from 200 Mbps to 500 Mbps
        $this->actingAs($resellerUser);
        $storeResp = $this->post(route('links.service-requests.store', $link), [
            'type' => 'UPGRADE',
            'requested_bandwidth_mbps' => 500,
            'reason' => 'Corporate client scaling traffic for peak season',
        ]);
        $storeResp->assertRedirect(route('links.show', $link));
        $storeResp->assertSessionHas('success');

        $sr = ServiceRequest::where('link_id', $link->id)->latest()->first();
        $this->assertNotNull($sr);
        $this->assertEquals('UPGRADE', $sr->type);
        $this->assertEquals(200, $sr->old_bandwidth_mbps);
        $this->assertEquals(500, $sr->requested_bandwidth_mbps);
        // Stage 1: Initial status must be PENDING_BILLING
        $this->assertEquals(ServiceRequest::STATUS_PENDING_BILLING, $sr->status);

        // 2. Billing Reviews and Approves Commercial Terms (updates new monthly charge)
        $this->actingAs($billingUser);
        $billingResp = $this->post(route('service-requests.decide', $sr), [
            'action' => 'billing_approve',
            'new_monthly_charge' => 65000.00,
            'remarks' => 'Commercial rate approved at ৳65,000/mo',
        ]);
        $billingResp->assertRedirect();
        $billingResp->assertSessionHas('success');

        $sr->refresh();
        // Stage 2: Billing approved -> PENDING_MD
        $this->assertEquals(ServiceRequest::STATUS_PENDING_MD, $sr->status);
        $this->assertEquals(65000.00, (float) $sr->new_monthly_charge);

        // 3. MD Approves to TX Team
        $this->actingAs($mdUser);
        $mdResp = $this->post(route('service-requests.decide', $sr), [
            'action' => 'md_approve',
            'remarks' => 'Approved. Forwarded to Transmission team to coordinate with Summit NTTN.',
        ]);
        $mdResp->assertRedirect();
        $mdResp->assertSessionHas('success');

        $sr->refresh();
        // Stage 3: MD approved -> PENDING_TX
        $this->assertEquals(ServiceRequest::STATUS_PENDING_TX, $sr->status);

        // 4. Transmission Coordinates with NTTN and Finalizes Circuit & Live Bill
        $this->actingAs($txUser);
        $txResp = $this->post(route('service-requests.decide', $sr), [
            'action' => 'tx_implement',
            'tx_remarks' => 'Summit Communications updated port policer & CIR to 500 Mbps. Verification latency 4ms.',
        ]);
        $txResp->assertRedirect();
        $txResp->assertSessionHas('success');

        $sr->refresh();
        $link->refresh();

        // Stage 4: Status completed, link bandwidth updated, and live billing directly finalized!
        $this->assertEquals(ServiceRequest::STATUS_COMPLETED, $sr->status);
        $this->assertEquals('Summit Communications updated port policer & CIR to 500 Mbps. Verification latency 4ms.', $sr->tx_remarks);
        $this->assertEquals(500, $link->bandwidth_mbps);
        $this->assertEquals(65000.00, (float) $link->billing->monthly_charge);
        $this->assertEquals(Billing::STATUS_COMPLETED, $link->billing->status);

        // 5. Subsequent Bandwidth DOWNGRADE Workflow test (500M -> 100M)
        $this->actingAs($resellerUser);
        $downStoreResp = $this->post(route('links.service-requests.store', $link), [
            'type' => 'DOWNGRADE',
            'requested_bandwidth_mbps' => 100,
            'reason' => 'Off-season capacity optimization',
        ]);
        $downStoreResp->assertRedirect(route('links.show', $link));

        $downSr = ServiceRequest::where('link_id', $link->id)->latest('id')->first();
        $this->assertNotNull($downSr);
        $this->assertEquals('DOWNGRADE', $downSr->type);
        $this->assertEquals(500, $downSr->old_bandwidth_mbps);
        $this->assertEquals(100, $downSr->requested_bandwidth_mbps);
        $this->assertEquals(ServiceRequest::STATUS_PENDING_BILLING, $downSr->status);

        // Billing sets discounted downgrade rate
        $this->actingAs($billingUser);
        $this->post(route('service-requests.decide', $downSr), [
            'action' => 'billing_approve',
            'new_monthly_charge' => 18000.00,
            'remarks' => 'Revised commercial terms for 100M',
        ]);
        $downSr->refresh();
        $this->assertEquals(ServiceRequest::STATUS_PENDING_MD, $downSr->status);

        // MD approves downgrade
        $this->actingAs($mdUser);
        $this->post(route('service-requests.decide', $downSr), ['action' => 'md_approve']);
        $downSr->refresh();
        $this->assertEquals(ServiceRequest::STATUS_PENDING_TX, $downSr->status);

        // TX implements with NTTN and directly finalizes new bill
        $this->actingAs($txUser);
        $this->post(route('service-requests.decide', $downSr), [
            'action' => 'tx_implement',
            'tx_remarks' => 'Summit Communications throttled CIR to 100 Mbps',
        ]);
        $downSr->refresh();
        $link->refresh();

        $this->assertEquals(ServiceRequest::STATUS_COMPLETED, $downSr->status);
        $this->assertEquals(100, $link->bandwidth_mbps);
        $this->assertEquals(18000.00, (float) $link->billing->monthly_charge);
    }

    public function test_ticket_picture_sharing_and_customer_up_verification_locking(): void
    {
        Storage::fake('public');
        $this->seed();

        $resellerUser = User::whereHas('role', fn ($q) => $q->where('name', Role::RESELLER))->first();
        $nocUser = User::whereHas('role', fn ($q) => $q->where('name', Role::NOC))->first();
        $link = Link::where('link_id', 'LNK-CTG-0042')->first();
        $this->assertNotNull($link);

        // 1. Reseller creates a Trouble Ticket with a picture attachment
        $fakeCutPhoto = UploadedFile::fake()->create('fiber_cut_photo.jpg', 150, 'image/jpeg');
        $this->actingAs($resellerUser);
        $createResp = $this->post(route('tickets.store'), [
            'link_id' => $link->id,
            'type' => 'Total Optical Loss (Fiber Cut)',
            'priority' => 'CRITICAL',
            'description' => 'Excavation backhoe damaged optical duct near GEC circle',
            'attachment' => $fakeCutPhoto,
        ]);
        $createResp->assertRedirect();
        $createResp->assertSessionHas('success');

        $ticket = Ticket::where('link_id', $link->id)->latest('id')->first();
        $this->assertNotNull($ticket);
        $this->assertNotNull($ticket->attachment_path);
        Storage::disk('public')->assertExists($ticket->attachment_path);
        $this->assertEquals(Ticket::STATUS_OPEN, $ticket->status);
        $this->assertFalse($ticket->isLocked());

        // 2. NOC responds and shares an OTDR trace picture in the ticket thread
        $this->actingAs($nocUser);
        $fakeOtdrPic = UploadedFile::fake()->create('otdr_trace_fault.png', 200, 'image/png');
        $msgResp = $this->post(route('tickets.messages.send', $ticket), [
            'message' => 'OTDR fault locator pinpoints break at 2.45km from Node',
            'attachment' => $fakeOtdrPic,
        ]);
        $msgResp->assertRedirect();
        $msgResp->assertSessionHas('success');

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $nocUser->id,
        ]);
        $latestMsg = $ticket->messages()->latest('id')->first();
        $this->assertNotNull($latestMsg->attachment_path);
        Storage::disk('public')->assertExists($latestMsg->attachment_path);

        // 3. NOC updates status to RESOLVED with splicing completion photo
        $fakeSplicePic = UploadedFile::fake()->create('splice_box_restored.jpg', 180, 'image/jpeg');
        $updateResp = $this->post(route('tickets.update-status', $ticket), [
            'status' => 'RESOLVED',
            'assigned_to' => $nocUser->id,
            'remarks' => 'Joint box respliced, Rx level normalized to -16dBm',
            'attachment' => $fakeSplicePic,
        ]);
        $updateResp->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(Ticket::STATUS_RESOLVED, $ticket->status);
        $this->assertTrue($ticket->canBeVerified());
        $this->assertFalse($ticket->isLocked());

        // 4. Customer verifies connection is UP and finalizes closure
        $this->actingAs($resellerUser);
        $verifyResp = $this->post(route('tickets.verify-up', $ticket));
        $verifyResp->assertRedirect();
        $verifyResp->assertSessionHas('success');

        $ticket->refresh();
        $this->assertEquals(Ticket::STATUS_CLOSED, $ticket->status);
        $this->assertTrue($ticket->is_customer_verified);
        $this->assertNotNull($ticket->verified_at);
        $this->assertTrue($ticket->isLocked());

        // 5. Verify the ticket is completely locked: no further status updates or messages allowed
        $this->actingAs($nocUser);
        $blockedUpdate = $this->post(route('tickets.update-status', $ticket), [
            'status' => 'WORKING',
            'remarks' => 'Attempting to reopen or change status',
        ]);
        $blockedUpdate->assertSessionHas('error');
        $ticket->refresh();
        $this->assertEquals(Ticket::STATUS_CLOSED, $ticket->status);

        $blockedMsg = $this->post(route('tickets.messages.send', $ticket), [
            'message' => 'Attempting to send message to locked ticket',
        ]);
        $blockedMsg->assertSessionHas('error');

        // 6. Verify index view displays "View History"
        $indexResp = $this->get(route('tickets.index'));
        $indexResp->assertOk();
        $indexResp->assertSee('View History');
    }

    public function test_reseller_ticket_panel_has_only_open_and_close_verify_options(): void
    {
        $this->seed();

        $resellerUser = User::whereHas('role', fn ($q) => $q->where('name', Role::RESELLER))->first();
        $nocUser = User::whereHas('role', fn ($q) => $q->where('name', Role::NOC))->first();
        $link = Link::where('link_id', 'LNK-CTG-0042')->first();

        $ticket = Ticket::create([
            'ticket_no' => 'TT-2026-RESELLER-RESTRICT',
            'link_id' => $link->id,
            'created_by' => $resellerUser->id,
            'type' => 'Packet Drop & Jitter',
            'priority' => 'HIGH',
            'description' => 'Packet drop observed on uplink',
            'status' => Ticket::STATUS_OPEN,
        ]);

        // 1. Reseller visits ticket view
        $this->actingAs($resellerUser);
        $resellerView = $this->get(route('tickets.show', $ticket));
        $resellerView->assertOk();
        $resellerView->assertSee('Subscriber Ticket Action');
        $resellerView->assertSee('OPEN (Issue Ongoing');
        $resellerView->assertSee('CLOSED (Customer UP');
        // Reseller must NOT see engineer assignment or internal working status
        $resellerView->assertDontSee('Assigned NOC / Field Engineer');
        $resellerView->assertDontSee('Under Splicing/Inspection');

        // Reseller cannot submit internal status
        $invalidResp = $this->post(route('tickets.update-status', $ticket), [
            'status' => 'WORKING',
        ]);
        $invalidResp->assertSessionHasErrors('status');

        // 2. NOC visits ticket view: has all options
        $this->actingAs($nocUser);
        $nocView = $this->get(route('tickets.show', $ticket));
        $nocView->assertOk();
        $nocView->assertSee('Update Ticket');
        $nocView->assertSee('Assigned NOC / Field Engineer');
        $nocView->assertSee('WORKING (Under Splicing/Inspection)');
        $nocView->assertSee('RESOLVED (Restored to SLA)');
    }

    public function test_auto_prune_expired_not_feasible_and_rejected_records_after_30_days(): void
    {
        $superAdminRole = Role::create(['name' => Role::SUPER_ADMIN]);
        $resellerRole = Role::create(['name' => Role::RESELLER]);

        $admin = User::create([
            'role_id' => $superAdminRole->id,
            'name' => 'System Admin',
            'email' => 'admin_prune@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $resellerUser = User::create([
            'role_id' => $resellerRole->id,
            'name' => 'ISP Prune User',
            'email' => 'isp_prune@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $reseller = Reseller::create([
            'user_id' => $resellerUser->id,
            'reseller_code' => 'RSL-PRUNE',
            'company_name' => 'Prune Test ISP',
            'phone' => '01800000000',
        ]);

        $customer = Customer::create([
            'reseller_id' => $reseller->id,
            'name' => 'Corporate Customer',
            'contact_person' => 'Manager',
            'phone' => '01900000000',
            'district' => 'Dhaka',
            'thana' => 'Motijheel',
            'address' => 'Motijheel C/A',
        ]);

        // 1. Expired NOT_FEASIBLE survey (35 days old)
        $expiredNotFeasible = SurveyRequest::create([
            'request_no' => 'SRV-NOT-FEASIBLE-OLD',
            'reseller_id' => $reseller->id,
            'customer_id' => $customer->id,
            'bandwidth_mbps' => 100,
            'status' => SurveyRequest::STATUS_NOT_FEASIBLE,
            'requested_at' => now()->subDays(40),
        ]);
        DB::table('survey_requests')->where('id', $expiredNotFeasible->id)->update([
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(35),
        ]);

        // 2. Expired MD_REJECTED survey (31 days old)
        $expiredRejected = SurveyRequest::create([
            'request_no' => 'SRV-REJECTED-OLD',
            'reseller_id' => $reseller->id,
            'customer_id' => $customer->id,
            'bandwidth_mbps' => 200,
            'status' => SurveyRequest::STATUS_MD_REJECTED,
            'requested_at' => now()->subDays(35),
        ]);
        DB::table('survey_requests')->where('id', $expiredRejected->id)->update([
            'created_at' => now()->subDays(35),
            'updated_at' => now()->subDays(31),
        ]);

        // 3. Recent NOT_FEASIBLE survey (8 hours old / 1 day old)
        $recentNotFeasible = SurveyRequest::create([
            'request_no' => 'SRV-NOT-FEASIBLE-RECENT',
            'reseller_id' => $reseller->id,
            'customer_id' => $customer->id,
            'bandwidth_mbps' => 50,
            'status' => SurveyRequest::STATUS_NOT_FEASIBLE,
            'requested_at' => now()->subHours(8),
        ]);
        DB::table('survey_requests')->where('id', $recentNotFeasible->id)->update([
            'created_at' => now()->subHours(8),
            'updated_at' => now()->subHours(8),
        ]);

        // 4. In-flight active survey (40 days old, but ACTIVE status)
        $activeSurvey = SurveyRequest::create([
            'request_no' => 'SRV-ACTIVE-SURVEY-OLD',
            'reseller_id' => $reseller->id,
            'customer_id' => $customer->id,
            'bandwidth_mbps' => 500,
            'status' => SurveyRequest::STATUS_ACTIVE,
            'requested_at' => now()->subDays(40),
        ]);
        DB::table('survey_requests')->where('id', $activeSurvey->id)->update([
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);

        // 5. Expired Terminated Link (32 days old)
        $expiredTerminatedLink = Link::create([
            'link_id' => 'LNK-EXPIRED-TERMINATED',
            'customer_id' => $customer->id,
            'reseller_id' => $reseller->id,
            'bandwidth_mbps' => 50,
            'status' => Link::STATUS_TERMINATED,
        ]);
        DB::table('links')->where('id', $expiredTerminatedLink->id)->update([
            'created_at' => now()->subDays(60),
            'updated_at' => now()->subDays(32),
        ]);

        // 6. Active Link (50 days old)
        $activeLink = Link::create([
            'link_id' => 'LNK-ACTIVE-OLD',
            'customer_id' => $customer->id,
            'reseller_id' => $reseller->id,
            'bandwidth_mbps' => 100,
            'status' => Link::STATUS_ACTIVE,
        ]);
        DB::table('links')->where('id', $activeLink->id)->update([
            'created_at' => now()->subDays(50),
            'updated_at' => now()->subDays(50),
        ]);

        // 7. Expired Rejected Service Request (33 days old)
        $expiredRejectedServiceReq = ServiceRequest::create([
            'request_no' => 'SR-EXPIRED-REJECTED',
            'link_id' => $activeLink->id,
            'type' => ServiceRequest::TYPE_UPGRADE,
            'old_bandwidth_mbps' => 100,
            'requested_bandwidth_mbps' => 200,
            'status' => ServiceRequest::STATUS_REJECTED,
            'created_by' => $resellerUser->id,
        ]);
        DB::table('service_requests')->where('id', $expiredRejectedServiceReq->id)->update([
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(33),
        ]);

        // Test Artisan command execution
        $this->artisan('surveys:prune-expired', ['--days' => 30])
            ->expectsOutputToContain('Pruning expired surveys and links older than 30 days...')
            ->assertExitCode(0);

        // Assert expired items are deleted
        $this->assertDatabaseMissing('survey_requests', ['id' => $expiredNotFeasible->id]);
        $this->assertDatabaseMissing('survey_requests', ['id' => $expiredRejected->id]);
        $this->assertDatabaseMissing('links', ['id' => $expiredTerminatedLink->id]);
        $this->assertDatabaseMissing('service_requests', ['id' => $expiredRejectedServiceReq->id]);

        // Assert recent and active items are preserved
        $this->assertDatabaseHas('survey_requests', ['id' => $recentNotFeasible->id]);
        $this->assertDatabaseHas('survey_requests', ['id' => $activeSurvey->id]);
        $this->assertDatabaseHas('links', ['id' => $activeLink->id]);

        // Also verify visiting dashboard triggers pruning automatically
        $anotherExpired = SurveyRequest::create([
            'request_no' => 'SRV-ANOTHER-EXPIRED',
            'reseller_id' => $reseller->id,
            'customer_id' => $customer->id,
            'bandwidth_mbps' => 100,
            'status' => SurveyRequest::STATUS_NOT_FEASIBLE,
            'requested_at' => now()->subDays(45),
        ]);
        DB::table('survey_requests')->where('id', $anotherExpired->id)->update([
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDays(35),
        ]);
        $this->assertDatabaseHas('survey_requests', ['id' => $anotherExpired->id]);
        $this->assertDatabaseHas('survey_requests', ['id' => $anotherExpired->id]);

        $this->actingAs($admin);
        $response = $this->get(route('dashboard'));
        $response->assertOk();

        // Should now be purged automatically by visiting dashboard
        $this->assertDatabaseMissing('survey_requests', ['id' => $anotherExpired->id]);
    }

    public function test_infeasible_link_displays_only_company_and_no_financial_info_and_no_approval_selection_button(): void
    {
        $this->seed();

        $resellerUser = User::whereHas('role', fn ($q) => $q->where('name', Role::RESELLER))->first();
        $txUser = User::whereHas('role', fn ($q) => $q->where('name', Role::TRANSMISSION))->first();
        $mdUser = User::whereHas('role', fn ($q) => $q->where('name', Role::MD))->first();

        $this->actingAs($resellerUser);
        $this->post(route('surveys.store'), [
            'customer_name' => 'Dual Carrier Test Customer',
            'customer_phone' => '01711112233',
            'customer_address' => 'Gulshan 2, Dhaka',
            'district' => 'Dhaka',
            'thana' => 'Gulshan',
            'bandwidth_mbps' => 200,
            'service_type' => 'Data Link',
        ]);

        $survey = SurveyRequest::latest()->first();

        // Transmission submits 2 providers:
        // Provider 1 (Fiber@Home): NOT FEASIBLE (feasible = 0) with installation_cost 99999, monthly_cost 88888
        // Provider 2 (Summit): FEASIBLE (feasible = 1) with installation_cost 35000, monthly_cost 45000
        $this->actingAs($txUser);
        $resp = $this->post(route('surveys.feedback', $survey), [
            'provider' => 'Fiber@Home Infeasible Ltd',
            'feasible' => 0,
            'fiber_distance_km' => 15.5,
            'nearest_pop' => 'Gulshan Core POP',
            'installation_cost' => 99999,
            'monthly_cost' => 88888,
            'expected_delivery_days' => 10,
            'feedback' => 'Duct blocked completely near bridge',
            'has_provider_2' => '1',
            'provider_2' => 'Summit Feasible Net',
            'feasible_2' => 1,
            'fiber_distance_km_2' => 3.2,
            'nearest_pop_2' => 'Summit Gulshan POP',
            'installation_cost_2' => 35000,
            'monthly_cost_2' => 45000,
            'expected_delivery_days_2' => 5,
        ]);
        $resp->assertRedirect();

        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_MD_APPROVAL_PENDING, $survey->status);

        $fbInfeasible = $survey->feedbacks->where('provider', 'Fiber@Home Infeasible Ltd')->first();
        $fbFeasible = $survey->feedbacks->where('provider', 'Summit Feasible Net')->first();
        $this->assertFalse($fbInfeasible->feasible);
        $this->assertFalse($fbInfeasible->is_selected);
        $this->assertTrue($fbFeasible->feasible);
        $this->assertTrue($fbFeasible->is_selected);

        // View portal (surveys.show) as MD
        $this->actingAs($mdUser);
        $viewResp = $this->get(route('surveys.show', $survey));
        $viewResp->assertOk();

        // 1. Shows company name and 'ইনফিজিবল' / 'INFEASIBLE'
        $viewResp->assertSee('Fiber@Home Infeasible Ltd');
        $viewResp->assertSee('ইনফিজিবল');

        // 2. No financial info displayed for infeasible link (৳ 99,999 and ৳ 88,888 must NOT be visible)
        $viewResp->assertDontSee('99,999');
        $viewResp->assertDontSee('88,888');

        // 3. Feasible link financial info IS visible
        $viewResp->assertSee('Summit Feasible Net');
        $viewResp->assertSee('35,000');
        $viewResp->assertSee('45,000');

        // 4. Infeasible link has NO radio/selection button on approval desk
        $viewResp->assertDontSee('value="'.$fbInfeasible->id.'"', false);

        // 5. Attempting to approve the infeasible feedback is rejected by validation
        $invalidApproveResp = $this->post(route('approvals.decide', $survey), [
            'decision' => 'approve',
            'selected_feedback_id' => $fbInfeasible->id,
        ]);
        $invalidApproveResp->assertSessionHasErrors('selected_feedback_id');

        // 6. Approving the feasible feedback succeeds
        $approveResp = $this->post(route('approvals.decide', $survey), [
            'decision' => 'approve',
            'selected_feedback_id' => $fbFeasible->id,
            'remarks' => 'Approved feasible Summit route',
        ]);
        $approveResp->assertRedirect();

        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_MD_APPROVED, $survey->status);
        $this->assertEquals($fbFeasible->id, $survey->selectedFeedback->id);

        // 7. Reseller issues client Work Order
        $this->actingAs($resellerUser);
        $woResp = $this->post(route('surveys.issue-work-order', $survey), [
            'client_po_no' => 'PO-TEST-1234',
            'deadline_days' => 7,
            'instructions' => 'Proceed with field fiber deployment via Summit',
        ]);
        $woResp->assertRedirect();

        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_WORK_ORDER_ISSUED, $survey->status);
        $this->assertNotNull($survey->workOrder);

        // 8. After Work Order is issued, verify that the INFEASIBLE table/card is completely gone
        $postWoResp = $this->get(route('surveys.show', $survey));
        $postWoResp->assertOk();
        $postWoResp->assertDontSee('ইনফিজিবল');
        // Only the link that was given the work order is present in the feasibility report and proceeds to next steps
        $postWoResp->assertSee('Approved NTTN Route');
        $postWoResp->assertSee('Summit Feasible Net');
        $postWoResp->assertSee('Work Order: PO-TEST-1234');
    }

    public function test_reseller_can_access_billing_and_view_total_active_circuits_and_bill(): void
    {
        $this->seed();

        $resellerUser = User::where('email', 'reseller@isp.test')->first();
        $this->actingAs($resellerUser);

        // 1. Verify Reseller sees Billing option in sidebar navigation
        $dashResp = $this->get(route('dashboard'));
        $dashResp->assertOk();
        $dashResp->assertSee('Billing & Invoices', false);

        // 2. Reseller visits billing page
        $billingResp = $this->get(route('billing.index'));
        $billingResp->assertOk();
        $billingResp->assertSee('Total Active Monthly Bill');
        $billingResp->assertSee('Active Circuits Consolidated Billing Breakdown');
        $billingResp->assertSee('LNK-CTG-0042');
        $billingResp->assertSee('SCL-CTG-AGR-889');

        // 3. Verify total bill for active circuits is visible
        $activeLink = Link::where('link_id', 'LNK-CTG-0042')->first();
        if ($activeLink && $activeLink->billing) {
            $billingResp->assertSee(number_format($activeLink->billing->monthly_charge, 2));
        }
    }

    public function test_reseller_cannot_see_another_reseller_billing_and_circuits(): void
    {
        $this->seed();

        $resellerUser1 = User::where('email', 'reseller@isp.test')->first();
        $resellerUser2 = User::where('email', 'reseller2@isp.test')->first();

        // 1. Reseller 1 logs in
        $this->actingAs($resellerUser1);
        $resp1 = $this->get(route('billing.index'));
        $resp1->assertOk();

        // Reseller 1 sees their own circuit and bill
        $resp1->assertSee('LNK-CTG-0042');
        $resp1->assertSee('45,000.00');

        // Reseller 1 MUST NOT see Reseller 2's circuit, company name, or bill
        $resp1->assertDontSee('LNK-SYL-0088');
        $resp1->assertDontSee('Metro Net Communication Ltd.');
        $resp1->assertDontSee('30,000.00');

        // Reseller 1 tries to bypass by passing reseller_id of Reseller 2 in query string
        $reseller2 = Reseller::where('company_name', 'Metro Net Communication Ltd.')->first();
        $tamperedResp = $this->get(route('billing.index', ['reseller_id' => $reseller2->id]));
        $tamperedResp->assertOk();
        $tamperedResp->assertSee('LNK-CTG-0042');
        $tamperedResp->assertDontSee('LNK-SYL-0088');
        $tamperedResp->assertDontSee('Metro Net Communication Ltd.');

        // 2. Reseller 2 logs in
        $this->actingAs($resellerUser2);
        $resp2 = $this->get(route('billing.index'));
        $resp2->assertOk();

        // Reseller 2 sees their own circuit and bill
        $resp2->assertSee('LNK-SYL-0088');
        $resp2->assertSee('30,000.00');

        // Reseller 2 MUST NOT see Reseller 1's circuit, company name, or bill
        $resp2->assertDontSee('LNK-CTG-0042');
        $resp2->assertDontSee('Apex Fiber Net Ltd.');
        $resp2->assertDontSee('45,000.00');
    }

    public function test_md_and_billing_can_see_all_resellers_bills_and_filter(): void
    {
        $this->seed();

        $mdUser = User::where('email', 'md@nttn.test')->first();
        $billingUser = User::where('email', 'billing@nttn.test')->first();

        // 1. MD Sir views billing portal
        $this->actingAs($mdUser);
        $mdResp = $this->get(route('billing.index'));
        $mdResp->assertOk();

        // MD sees BOTH Reseller 1 and Reseller 2 circuits
        $mdResp->assertSee('LNK-CTG-0042');
        $mdResp->assertSee('LNK-SYL-0088');
        $mdResp->assertSee('Apex Fiber Net Ltd.');
        $mdResp->assertSee('Metro Net Communication Ltd.');

        // MD sees combined total bill: 45,000 + 30,000 = 75,000
        $mdResp->assertSee('75,000.00');
        $mdResp->assertSee('All Resellers (সকল রিসেলার)');

        // MD filters by Reseller 2
        $reseller2 = Reseller::where('company_name', 'Metro Net Communication Ltd.')->first();
        $filteredMdResp = $this->get(route('billing.index', ['reseller_id' => $reseller2->id]));
        $filteredMdResp->assertOk();
        $filteredMdResp->assertSee('LNK-SYL-0088');
        $filteredMdResp->assertSee('30,000.00');
        $filteredMdResp->assertDontSee('LNK-CTG-0042');

        // 2. Billing Manager views billing portal
        $this->actingAs($billingUser);
        $billingResp = $this->get(route('billing.index'));
        $billingResp->assertOk();

        // Billing manager sees ALL circuits across both resellers
        $billingResp->assertSee('LNK-CTG-0042');
        $billingResp->assertSee('LNK-SYL-0088');
        $billingResp->assertSee('Apex Fiber Net Ltd.');
        $billingResp->assertSee('Metro Net Communication Ltd.');
        $billingResp->assertSee('75,000.00');
    }

    public function test_reseller_dashboard_and_modules_completely_isolate_other_reseller_info(): void
    {
        $this->seed();

        $resellerUser1 = User::where('email', 'reseller@isp.test')->first();
        $resellerUser2 = User::where('email', 'reseller2@isp.test')->first();

        // 1. Reseller 2 (Metro Net) visits dashboard
        $this->actingAs($resellerUser2);
        $dashResp2 = $this->get(route('dashboard'));
        $dashResp2->assertOk();

        // In Dashboard KPI cards, Reseller 2 should see only their own circuit count (1) and surveys count (0)
        $dashResp2->assertSee('Your survey pipeline');
        $dashResp2->assertSee('Your active circuits');
        $dashResp2->assertDontSee('Apex Fiber Net Ltd.');
        $dashResp2->assertDontSee('Farhan Ahmed');

        // 2. Reseller 2 tries to access Reseller 1's survey request (REQ-2026-001)
        $survey1 = SurveyRequest::where('request_no', 'REQ-2026-001')->first();
        $surveyResp = $this->get(route('surveys.show', $survey1));
        $surveyResp->assertStatus(403);

        // 3. Reseller 2 tries to access Reseller 1's active link (LNK-CTG-0042)
        $link1 = Link::where('link_id', 'LNK-CTG-0042')->first();
        $linkResp = $this->get(route('links.show', $link1));
        $linkResp->assertStatus(403);

        // 4. Reseller 2 tries to access Reseller 1's ticket (TT-2026-0010)
        $ticket1 = Ticket::where('ticket_no', 'TT-2026-0010')->first();
        $ticketResp = $this->get(route('tickets.show', $ticket1));
        $ticketResp->assertStatus(403);

        // 5. Reseller 2 tries to access Reseller 1's circuit chat
        $chatResp = $this->get(route('links.chat', $link1));
        $chatResp->assertStatus(403);

        // 6. Reseller 2 visits activity logs
        $logsResp = $this->get(route('activity-logs.index'));
        $logsResp->assertOk();
        $logsResp->assertDontSee('Apex Fiber Net Ltd.');
        $logsResp->assertDontSee('Farhan Ahmed');
    }
}
