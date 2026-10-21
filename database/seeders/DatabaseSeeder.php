<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\Billing;
use App\Models\Customer;
use App\Models\Link;
use App\Models\Reseller;
use App\Models\Role;
use App\Models\SurveyFeedback;
use App\Models\SurveyRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $roles = [
            Role::SUPER_ADMIN => Role::firstOrCreate(['name' => Role::SUPER_ADMIN]),
            Role::MD => Role::firstOrCreate(['name' => Role::MD]),
            Role::RESELLER => Role::firstOrCreate(['name' => Role::RESELLER]),
            Role::TRANSMISSION => Role::firstOrCreate(['name' => Role::TRANSMISSION]),
            Role::NOC => Role::firstOrCreate(['name' => Role::NOC]),
            Role::BILLING => Role::firstOrCreate(['name' => Role::BILLING]),
        ];

        // 2. Demo Users for Each Role (Password: 'password')
        $admin = User::firstOrCreate(['email' => 'admin@nttn.test'], [
            'role_id' => $roles[Role::SUPER_ADMIN]->id,
            'name' => 'System Super Admin',
            'phone' => '+8801700000001',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $md = User::firstOrCreate(['email' => 'md@nttn.test'], [
            'role_id' => $roles[Role::MD]->id,
            'name' => 'Khandaker Rafiqul Islam (MD)',
            'phone' => '+8801700000002',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $resellerUser = User::firstOrCreate(['email' => 'reseller@isp.test'], [
            'role_id' => $roles[Role::RESELLER]->id,
            'name' => 'Farhan Ahmed (Apex ISP)',
            'phone' => '+8801700000003',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $txUser = User::firstOrCreate(['email' => 'tx@nttn.test'], [
            'role_id' => $roles[Role::TRANSMISSION]->id,
            'name' => 'Tanvir Hossain (Transmission Eng)',
            'phone' => '+8801700000004',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $nocUser = User::firstOrCreate(['email' => 'noc@nttn.test'], [
            'role_id' => $roles[Role::NOC]->id,
            'name' => 'Mahmudul Hasan (NOC Lead)',
            'phone' => '+8801700000005',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $billingUser = User::firstOrCreate(['email' => 'billing@nttn.test'], [
            'role_id' => $roles[Role::BILLING]->id,
            'name' => 'Shamim Reza (Billing Mgr)',
            'phone' => '+8801700000006',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // 3. Reseller Profile
        $reseller = Reseller::firstOrCreate(['user_id' => $resellerUser->id], [
            'reseller_code' => 'RSL-1001',
            'company_name' => 'Apex Fiber Net Ltd.',
            'address' => 'House 14, Road 7, Dhanmondi, Dhaka 1205',
            'phone' => '+8801700000003',
        ]);

        // 4. Sample Customers
        $customer1 = Customer::firstOrCreate(['name' => 'Dhaka FinTech Tower Ltd.'], [
            'reseller_id' => $reseller->id,
            'phone' => '+8801811223344',
            'address' => '71 Dilkusha Commercial Area',
            'district' => 'Dhaka',
            'thana' => 'Motijheel',
            'latitude' => 23.7297430,
            'longitude' => 90.4174310,
        ]);

        $customer2 = Customer::firstOrCreate(['name' => 'Port Logistics Enterprise'], [
            'reseller_id' => $reseller->id,
            'phone' => '+8801911556677',
            'address' => 'Agrabad Commercial Area',
            'district' => 'Chattogram',
            'thana' => 'Double Mooring',
            'latitude' => 22.3255000,
            'longitude' => 91.8142000,
        ]);

        // 5. Sample In-Flight Survey (Awaiting MD Approval)
        $survey1 = SurveyRequest::firstOrCreate(['request_no' => 'REQ-2026-001'], [
            'reseller_id' => $reseller->id,
            'customer_id' => $customer1->id,
            'bandwidth_mbps' => 500,
            'service_type' => 'Dark Fiber Transmission',
            'preferred_nttn' => 'Fiber@Home',
            'description' => 'Dedicated dark fiber ring between Motijheel FinTech Hub and Gulshan DC',
            'status' => SurveyRequest::STATUS_MD_APPROVAL_PENDING,
            'requested_at' => now()->subDays(3),
        ]);

        SurveyFeedback::firstOrCreate(['survey_request_id' => $survey1->id], [
            'provider' => 'Fiber@Home',
            'feasible' => true,
            'route' => 'Motijheel POP -> Dainik Bangla -> Press Club -> Gulshan Ring',
            'fiber_distance_km' => 7.450,
            'nearest_pop' => 'Motijheel Central POP #04',
            'port' => 'TenGigE-0/1/4',
            'vlan' => 'VLAN-3045',
            'installation_cost' => 45000.00,
            'monthly_cost' => 85000.00,
            'expected_delivery_days' => 5,
            'feedback' => 'Direct path cleared. Duct access verified without right-of-way congestion.',
            'created_by' => $txUser->id,
        ]);

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey1->id,
            action: 'SURVEY_REQUESTED',
            fromStatus: null,
            toStatus: SurveyRequest::STATUS_SURVEY_REQUESTED,
            remarks: 'Survey request lodged by Apex Fiber Net Ltd.',
            userId: $resellerUser->id
        );

        ActivityLog::record(
            entityType: 'survey_request',
            entityId: $survey1->id,
            action: 'FEEDBACK_SUBMITTED',
            fromStatus: SurveyRequest::STATUS_SURVEY_PROCESSING,
            toStatus: SurveyRequest::STATUS_FEEDBACK_READY,
            remarks: 'Transmission feasibility completed by Tanvir Hossain',
            userId: $txUser->id
        );

        // 6. Sample Completed Active Link
        $survey2 = SurveyRequest::firstOrCreate(['request_no' => 'REQ-2026-002'], [
            'reseller_id' => $reseller->id,
            'customer_id' => $customer2->id,
            'bandwidth_mbps' => 200,
            'service_type' => 'Direct Optical Transport',
            'preferred_nttn' => 'Summit Communications',
            'description' => 'Agrabad Port primary backbone',
            'status' => SurveyRequest::STATUS_ACTIVE,
            'requested_at' => now()->subDays(15),
        ]);

        $wo = WorkOrder::firstOrCreate(['work_order_no' => 'WO-2026-0001'], [
            'survey_request_id' => $survey2->id,
            'assigned_to' => $txUser->id,
            'status' => WorkOrder::STATUS_COMPLETED,
            'deadline' => now()->subDays(10),
            'instructions' => 'Splice fiber at Agrabad Node 3 patch panel port 8',
            'completed_at' => now()->subDays(8),
        ]);

        $link = Link::firstOrCreate(['link_id' => 'LNK-CTG-0042'], [
            'customer_id' => $customer2->id,
            'reseller_id' => $reseller->id,
            'work_order_id' => $wo->id,
            'bandwidth_mbps' => 200,
            'nttn_provider' => 'Summit Communications',
            'circuit_id' => 'SCL-CTG-AGR-889',
            'nttn_link_id' => 'SCL-NTTN-LNK-5524',
            'vlan' => 'VLAN-2110',
            'port' => 'GigE-1/0/12',
            'status' => Link::STATUS_ACTIVE,
            'activation_date' => now()->subDays(7),
        ]);

        Billing::firstOrCreate(['link_id' => $link->id], [
            'invoice_no' => 'INV-2026-0001',
            'installation_charge' => 25000.00,
            'monthly_charge' => 45000.00,
            'nttn_cost' => 30000.00,
            'billing_start_date' => now()->subDays(7),
            'due_date' => now()->addDays(23),
            'status' => Billing::STATUS_COMPLETED,
            'completed_by' => $billingUser->id,
        ]);

        Ticket::firstOrCreate(['ticket_no' => 'TT-2026-0010'], [
            'link_id' => $link->id,
            'created_by' => $resellerUser->id,
            'assigned_to' => $nocUser->id,
            'type' => 'High Optical Attenuation',
            'priority' => Ticket::PRIORITY_HIGH,
            'description' => 'Optical power dipped from -14dBm to -24dBm near midnight.',
            'status' => Ticket::STATUS_WORKING,
        ]);

        // 7. Second Reseller Demo Profile (Multi-Tenant Isolation Demo)
        $resellerUser2 = User::firstOrCreate(['email' => 'reseller2@isp.test'], [
            'role_id' => $roles[Role::RESELLER]->id,
            'name' => 'Kazi Nazrul (Metro Net ISP)',
            'phone' => '+8801700000007',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $reseller2 = Reseller::firstOrCreate(['user_id' => $resellerUser2->id], [
            'reseller_code' => 'RSL-1002',
            'company_name' => 'Metro Net Communication Ltd.',
            'address' => 'GEC Circle, Chattogram',
            'phone' => '+8801700000007',
        ]);

        $customer3 = Customer::firstOrCreate(['name' => 'Sylhet Tea Estate Corp.'], [
            'reseller_id' => $reseller2->id,
            'phone' => '+8801722334455',
            'address' => 'Zindabazar Commercial Area',
            'district' => 'Sylhet',
            'thana' => 'Kotwali',
            'latitude' => 24.8949,
            'longitude' => 91.8687,
        ]);

        $link2 = Link::firstOrCreate(['link_id' => 'LNK-SYL-0088'], [
            'customer_id' => $customer3->id,
            'reseller_id' => $reseller2->id,
            'work_order_id' => null,
            'bandwidth_mbps' => 100,
            'nttn_provider' => 'Fiber@Home',
            'circuit_id' => 'FAH-SYL-ZIN-102',
            'nttn_link_id' => 'FAH-NTTN-LNK-9921',
            'vlan' => 'VLAN-1502',
            'port' => 'GigE-0/0/2',
            'status' => Link::STATUS_ACTIVE,
            'activation_date' => now()->subDays(3),
        ]);

        Billing::firstOrCreate(['link_id' => $link2->id], [
            'invoice_no' => 'INV-2026-0002',
            'installation_charge' => 15000.00,
            'monthly_charge' => 30000.00,
            'nttn_cost' => 20000.00,
            'billing_start_date' => now()->subDays(3),
            'due_date' => now()->addDays(27),
            'status' => Billing::STATUS_COMPLETED,
            'completed_by' => $billingUser->id,
        ]);
    }
}
