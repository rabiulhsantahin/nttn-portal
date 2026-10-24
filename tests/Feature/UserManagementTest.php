<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Link;
use App\Models\Reseller;
use App\Models\Role;
use App\Models\SurveyRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $resellerUser;

    private User $nocUser;

    private array $roles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roles = [
            Role::SUPER_ADMIN => Role::firstOrCreate(['name' => Role::SUPER_ADMIN]),
            Role::MD => Role::firstOrCreate(['name' => Role::MD]),
            Role::RESELLER => Role::firstOrCreate(['name' => Role::RESELLER]),
            Role::TRANSMISSION => Role::firstOrCreate(['name' => Role::TRANSMISSION]),
            Role::NOC => Role::firstOrCreate(['name' => Role::NOC]),
            Role::BILLING => Role::firstOrCreate(['name' => Role::BILLING]),
        ];

        $this->superAdmin = User::create([
            'role_id' => $this->roles[Role::SUPER_ADMIN]->id,
            'name' => 'System Root Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('admin1234'),
            'is_active' => true,
        ]);

        $this->resellerUser = User::create([
            'role_id' => $this->roles[Role::RESELLER]->id,
            'name' => 'Apex Reseller',
            'email' => 'reseller@test.com',
            'password' => Hash::make('reseller1234'),
            'is_active' => true,
        ]);
        Reseller::create([
            'user_id' => $this->resellerUser->id,
            'reseller_code' => 'RSL-APEX',
            'company_name' => 'Apex ISP Ltd',
            'phone' => '01711111111',
        ]);

        $this->nocUser = User::create([
            'role_id' => $this->roles[Role::NOC]->id,
            'name' => 'NOC Engineer',
            'email' => 'noc@test.com',
            'password' => Hash::make('noc1234'),
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_access_user_management_index(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('users.index'));
        $response->assertOk();
        $response->assertSee('User & Role Access Management', false);
        $response->assertSee($this->superAdmin->name);
        $response->assertSee($this->resellerUser->name);
        $response->assertSee($this->nocUser->name);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $this->actingAs($this->nocUser);

        $response = $this->get(route('users.index'));
        $response->assertForbidden();

        $responseCreate = $this->get(route('users.create'));
        $responseCreate->assertForbidden();
    }

    public function test_super_admin_can_create_user_with_role_and_password(): void
    {
        $this->actingAs($this->superAdmin);

        $postData = [
            'name' => 'Tariq Transmission Lead',
            'email' => 'tariq.tx@test.com',
            'phone' => '+8801811112233',
            'password' => 'txsecret99',
            'role_id' => $this->roles[Role::TRANSMISSION]->id,
            'is_active' => '1',
        ];

        $response = $this->post(route('users.store'), $postData);
        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'tariq.tx@test.com',
            'name' => 'Tariq Transmission Lead',
            'role_id' => $this->roles[Role::TRANSMISSION]->id,
            'is_active' => true,
        ]);

        // Verify the newly created user can log in with their assigned password
        $this->post(route('logout'));

        $loginResponse = $this->post(route('login.post'), [
            'email' => 'tariq.tx@test.com',
            'password' => 'txsecret99',
        ]);
        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertEquals('tariq.tx@test.com', auth()->user()->email);
    }

    public function test_super_admin_can_create_reseller_user_with_profile(): void
    {
        $this->actingAs($this->superAdmin);

        $postData = [
            'name' => 'Delta Broadband Manager',
            'email' => 'delta@isp.test',
            'phone' => '+8801900112233',
            'password' => 'deltaisp2026',
            'role_id' => $this->roles[Role::RESELLER]->id,
            'reseller_company_name' => 'Delta Cyber Net Ltd',
            'reseller_code' => 'RSL-DELTA',
            'reseller_address' => 'Gulshan-1, Dhaka',
            'is_active' => '1',
        ];

        $response = $this->post(route('users.store'), $postData);
        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'delta@isp.test')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->reseller);
        $this->assertEquals('Delta Cyber Net Ltd', $user->reseller->company_name);
        $this->assertEquals('RSL-DELTA', $user->reseller->reseller_code);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        // Try logging into NOC account with Reseller's password or wrong password
        $response = $this->post(route('login.post'), [
            'email' => 'noc@test.com',
            'password' => 'reseller1234', // wrong password
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $this->nocUser->update(['is_active' => false]);

        $response = $this->post(route('login.post'), [
            'email' => 'noc@test.com',
            'password' => 'noc1234',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_super_admin_can_impersonate_any_user_and_stop_impersonating(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Super Admin logs in as NOC user
        $response = $this->post(route('users.impersonate', $this->nocUser));
        $response->assertRedirect(route('dashboard'));

        $this->assertEquals($this->nocUser->id, auth()->id());
        $this->assertEquals($this->superAdmin->id, session('impersonator_id'));

        // 2. While impersonating, the user has access to NOC features
        $nocView = $this->get(route('noc.index'));
        $nocView->assertOk();

        // 3. Super Admin stops impersonation and returns to Super Admin session
        $stopResponse = $this->post(route('users.stop-impersonate'));
        $stopResponse->assertRedirect(route('users.index'));

        $this->assertEquals($this->superAdmin->id, auth()->id());
        $this->assertFalse(session()->has('impersonator_id'));
    }

    public function test_non_admin_cannot_impersonate_or_use_quick_login(): void
    {
        $this->actingAs($this->resellerUser);

        // Attempt to impersonate NOC user
        $impersonateResponse = $this->post(route('users.impersonate', $this->nocUser));
        $impersonateResponse->assertForbidden();

        // Attempt to call quick-login
        $quickResponse = $this->post(route('login.quick'), [
            'email' => 'noc@test.com',
        ]);
        $quickResponse->assertForbidden();
    }

    public function test_super_admin_can_update_user_credentials_and_role(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->put(route('users.update', $this->nocUser), [
            'name' => 'Promoted Lead Engineer',
            'email' => 'promoted.noc@test.com',
            'phone' => '+8801755555555',
            'password' => 'newstrongpassword88',
            'role_id' => $this->roles[Role::MD]->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->nocUser->refresh();

        $this->assertEquals('Promoted Lead Engineer', $this->nocUser->name);
        $this->assertEquals('promoted.noc@test.com', $this->nocUser->email);
        $this->assertEquals($this->roles[Role::MD]->id, $this->nocUser->role_id);
        $this->assertTrue(Hash::check('newstrongpassword88', $this->nocUser->password));
    }

    public function test_super_admin_can_delete_user(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->delete(route('users.destroy', $this->nocUser));
        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $this->nocUser->id]);
    }

    public function test_super_admin_cannot_delete_own_account(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->delete(route('users.destroy', $this->superAdmin));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    public function test_login_page_renders_cleanly_without_demo_users_exposed(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertSee('Portal Sign In');
        $response->assertDontSee('admin@nttn.test');
        $response->assertDontSee('Khandaker Rafiqul Islam');
        $response->assertDontSee('ENTERPRISE ACCESS CONTROL');
    }

    public function test_super_admin_can_delete_survey_request(): void
    {
        $this->actingAs($this->superAdmin);

        $customer = Customer::create([
            'reseller_id' => $this->resellerUser->reseller->id,
            'name' => 'Test Customer Corp',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'district' => 'Dhaka',
        ]);

        $survey = SurveyRequest::create([
            'reseller_id' => $this->resellerUser->reseller->id,
            'customer_id' => $customer->id,
            'request_no' => 'REQ-TEST-DEL-01',
            'bandwidth_mbps' => 100,
            'service_type' => 'Dark Fiber',
            'status' => SurveyRequest::STATUS_SURVEY_REQUESTED,
        ]);

        $response = $this->delete(route('surveys.destroy', $survey));
        $response->assertRedirect(route('surveys.index'));
        $this->assertDatabaseMissing('survey_requests', ['id' => $survey->id]);
    }

    public function test_super_admin_can_delete_circuit_link(): void
    {
        $this->actingAs($this->superAdmin);

        $customer = Customer::create([
            'reseller_id' => $this->resellerUser->reseller->id,
            'name' => 'Link Test Customer',
            'phone' => '01700000001',
            'address' => 'Chittagong',
            'district' => 'Chittagong',
        ]);

        $link = Link::create([
            'customer_id' => $customer->id,
            'reseller_id' => $this->resellerUser->reseller->id,
            'link_id' => 'LNK-TEST-DEL-99',
            'bandwidth_mbps' => 50,
            'nttn_provider' => 'Fiber@Home',
            'circuit_id' => 'CKT-9999',
            'status' => Link::STATUS_ACTIVE,
        ]);

        $response = $this->delete(route('links.destroy', $link));
        $response->assertRedirect(route('links.index'));
        $this->assertDatabaseMissing('links', ['id' => $link->id]);
    }

    public function test_super_admin_can_delete_trouble_ticket(): void
    {
        $this->actingAs($this->superAdmin);

        $customer = Customer::create([
            'reseller_id' => $this->resellerUser->reseller->id,
            'name' => 'Ticket Test Customer',
            'phone' => '01700000002',
            'address' => 'Sylhet',
            'district' => 'Sylhet',
        ]);

        $link = Link::create([
            'customer_id' => $customer->id,
            'reseller_id' => $this->resellerUser->reseller->id,
            'link_id' => 'LNK-TT-01',
            'bandwidth_mbps' => 10,
            'status' => Link::STATUS_ACTIVE,
        ]);

        $ticket = Ticket::create([
            'ticket_no' => 'TT-TEST-DEL-01',
            'link_id' => $link->id,
            'created_by' => $this->superAdmin->id,
            'type' => 'Fiber Cut',
            'priority' => Ticket::PRIORITY_HIGH,
            'status' => Ticket::STATUS_OPEN,
            'description' => 'Test fiber cut incident',
        ]);

        $response = $this->delete(route('tickets.destroy', $ticket));
        $response->assertRedirect(route('tickets.index'));
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_non_admin_cannot_delete_survey_or_link(): void
    {
        $this->actingAs($this->nocUser);

        $customer = Customer::create([
            'reseller_id' => $this->resellerUser->reseller->id,
            'name' => 'Unauthorized Test Customer',
            'phone' => '01700000003',
            'address' => 'Khulna',
            'district' => 'Khulna',
        ]);

        $survey = SurveyRequest::create([
            'reseller_id' => $this->resellerUser->reseller->id,
            'customer_id' => $customer->id,
            'request_no' => 'REQ-NO-DEL',
            'bandwidth_mbps' => 10,
            'service_type' => 'Dark Fiber',
            'status' => SurveyRequest::STATUS_SURVEY_REQUESTED,
        ]);

        $response = $this->delete(route('surveys.destroy', $survey));
        $response->assertForbidden();
    }
}
