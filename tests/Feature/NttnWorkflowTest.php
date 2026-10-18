<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Link;
use App\Models\Reseller;
use App\Models\Role;
use App\Models\ServiceRequest;
use App\Models\SurveyRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'service_type' => 'Dark Fiber Transmission',
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

        // 5. STEP 3: MD Approves and issues Work Order
        $this->actingAs($mdUser);
        $apprResponse = $this->post(route('approvals.decide', $survey), [
            'decision' => 'approve',
            'remarks' => 'Approved as per business rate',
            'deadline_days' => 5,
        ]);
        $apprResponse->assertRedirect();
        $survey->refresh();
        $this->assertEquals(SurveyRequest::STATUS_WORK_ORDER_ISSUED, $survey->status);
        $this->assertNotNull($survey->workOrder);

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

        // 11. STEP 9: Bandwidth Upgrade Workflow
        $this->actingAs($resellerUser);
        $this->post(route('links.service-requests.store', $link), [
            'type' => 'UPGRADE',
            'requested_bandwidth_mbps' => 500,
            'reason' => 'Corporate bandwidth expansion',
        ]);
        $sr = ServiceRequest::first();
        $this->assertEquals(ServiceRequest::STATUS_PENDING, $sr->status);

        // MD Approves Upgrade
        $this->actingAs($mdUser);
        $this->post(route('service-requests.decide', $sr), ['action' => 'approve']);
        $sr->refresh();
        $this->assertEquals(ServiceRequest::STATUS_APPROVED, $sr->status);

        // NOC Implements Upgrade
        $this->actingAs($nocUser);
        $this->post(route('service-requests.decide', $sr), ['action' => 'implement']);
        $sr->refresh();
        $link->refresh();
        $this->assertEquals(ServiceRequest::STATUS_COMPLETED, $sr->status);
        $this->assertEquals(500, $link->bandwidth_mbps);

        // 12. STEP 10: Verify Activity Audit Logs
        $logsCount = ActivityLog::count();
        $this->assertGreaterThan(7, $logsCount);
    }
}
