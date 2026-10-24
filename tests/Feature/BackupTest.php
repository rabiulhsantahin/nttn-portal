<?php

namespace Tests\Feature;

use App\Mail\DatabaseBackupMail;
use App\Models\Role;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $resellerUser;

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
            'name' => 'Reseller User',
            'email' => 'reseller@test.com',
            'password' => Hash::make('reseller1234'),
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_backups(): void
    {
        $response = $this->get(route('backups.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_gets_forbidden_when_accessing_backups(): void
    {
        $response = $this->actingAs($this->resellerUser)->get(route('backups.index'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_view_backup_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('backups.index'));
        $response->assertStatus(200);
        $response->assertSee('Disaster Recovery');
        $response->assertSee('Daily Email Backup');
    }

    public function test_super_admin_can_generate_backup(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('backups.store'), ['action' => 'save']);

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success');

        $backupService = app(BackupService::class);
        $backups = $backupService->listBackups();
        $this->assertNotEmpty($backups);
        $this->assertStringEndsWith('.sql.gz', $backups[0]['filename']);
    }

    public function test_super_admin_can_update_backup_settings(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('backups.settings'), [
                'email' => 'noc-alerts@nttn.test',
                'enabled' => '1',
                'keep_days' => 45,
            ]);

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success');

        $backupService = app(BackupService::class);
        $settings = $backupService->getSettings();

        $this->assertEquals('noc-alerts@nttn.test', $settings['email']);
        $this->assertTrue($settings['enabled']);
        $this->assertEquals(45, $settings['keep_days']);
    }

    public function test_super_admin_can_send_backup_email_immediately(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('backups.email'), [
                'email' => 'disaster-recovery@nttn.test',
            ]);

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success');

        Mail::assertSent(DatabaseBackupMail::class, function ($mail) {
            return $mail->hasTo('disaster-recovery@nttn.test')
                && str_ends_with($mail->backup['filename'], '.sql.gz')
                && count($mail->attachments()) === 1;
        });
    }

    public function test_super_admin_can_download_and_delete_backup(): void
    {
        $backupService = app(BackupService::class);
        $backupInfo = $backupService->createBackup();

        $filename = $backupInfo['filename'];

        // Download
        $downloadResponse = $this->actingAs($this->superAdmin)
            ->get(route('backups.download', ['filename' => $filename]));

        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('content-disposition');

        // Delete
        $deleteResponse = $this->actingAs($this->superAdmin)
            ->delete(route('backups.destroy', ['filename' => $filename]));

        $deleteResponse->assertRedirect(route('backups.index'));
        $deleteResponse->assertSessionHas('success');

        $this->assertFileDoesNotExist($backupInfo['path']);
    }

    public function test_artisan_backup_command_runs_successfully(): void
    {
        Mail::fake();

        $this->artisan('backup:database', ['--email' => 'cron-backup@nttn.test'])
            ->expectsOutputToContain('Starting NTTN Portal database disaster recovery backup')
            ->expectsOutputToContain('successfully delivered to cron-backup@nttn.test')
            ->assertSuccessful();

        Mail::assertSent(DatabaseBackupMail::class, function ($mail) {
            return $mail->hasTo('cron-backup@nttn.test');
        });
    }

    public function test_super_admin_can_restore_database_from_uploaded_file(): void
    {
        $backupService = app(BackupService::class);
        $backupInfo = $backupService->createBackup();

        $uploadedFile = new UploadedFile(
            $backupInfo['path'],
            $backupInfo['filename'],
            'application/gzip',
            null,
            true
        );

        $response = $this->actingAs($this->superAdmin)
            ->post(route('backups.restore'), [
                'backup_file' => $uploadedFile,
                'confirm_restore' => '1',
            ]);

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success');
    }

    public function test_super_admin_can_restore_database_from_existing_archive(): void
    {
        $backupService = app(BackupService::class);
        $backupInfo = $backupService->createBackup();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('backups.restore'), [
                'filename' => $backupInfo['filename'],
                'confirm_restore' => '1',
            ]);

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success');
    }

    public function test_restore_fails_without_confirmation(): void
    {
        $backupService = app(BackupService::class);
        $backupInfo = $backupService->createBackup();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('backups.restore'), [
                'filename' => $backupInfo['filename'],
            ]);

        $response->assertSessionHasErrors('confirm_restore');
    }

    public function test_non_admin_cannot_restore_database(): void
    {
        $response = $this->actingAs($this->resellerUser)
            ->post(route('backups.restore'), [
                'confirm_restore' => '1',
            ]);

        $response->assertStatus(403);
    }
}
