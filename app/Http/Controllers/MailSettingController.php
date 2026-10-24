<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class MailSettingController extends Controller
{
    /**
     * Display the Mail & NTTN Carrier Configuration dashboard.
     */
    public function index(): View
    {
        $providers = SystemSetting::getNttnProviderEmails();

        $smtpConfig = [
            'mailer' => config('mail.default', 'log'),
            'host' => config('mail.mailers.smtp.host', '127.0.0.1'),
            'port' => config('mail.mailers.smtp.port', 2525),
            'encryption' => config('mail.mailers.smtp.encryption', 'tls'),
            'username' => config('mail.mailers.smtp.username', ''),
            'from_address' => config('mail.from.address', 'noc@nttn-portal.local'),
            'from_name' => config('mail.from.name', config('app.name', 'NTTN Portal')),
        ];

        return view('admin.mail_settings', compact('providers', 'smtpConfig'));
    }

    /**
     * Update NTTN Provider company destination mail addresses.
     */
    public function updateProviders(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'providers' => ['required', 'array'],
            'providers.*.name' => ['required', 'string', 'max:120'],
            'providers.*.email' => ['nullable', 'string', 'max:255'],
        ]);

        $directory = [];
        foreach ($validated['providers'] as $item) {
            $name = trim($item['name']);
            $email = trim($item['email'] ?? '');
            if (! empty($name)) {
                $directory[$name] = $email;
            }
        }

        // Add standard defaults if not present
        foreach (SystemSetting::defaultNttnProviders() as $defName => $defMail) {
            if (! isset($directory[$defName])) {
                $directory[$defName] = $defMail;
            }
        }

        SystemSetting::set('nttn_provider_emails', $directory);

        ActivityLog::record(
            entityType: 'settings',
            entityId: 0,
            action: 'NTTN_MAIL_DIRECTORY_UPDATED',
            fromStatus: null,
            toStatus: null,
            remarks: 'Super Admin updated NTTN Provider Company send-mail addresses.',
            userId: Auth::id()
        );

        return redirect()->route('admin.mail-settings.index')
            ->with('success', 'NTTN Provider Company send-mail addresses updated successfully!');
    }

    /**
     * Update Outgoing SMTP Mail Server configuration.
     */
    public function updateSmtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_mailer' => ['required', 'in:smtp,sendmail,log'],
            'mail_host' => ['nullable', 'string', 'max:190'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_encryption' => ['nullable', 'in:tls,ssl,none'],
            'mail_username' => ['nullable', 'string', 'max:190'],
            'mail_password' => ['nullable', 'string', 'max:190'],
            'mail_from_address' => ['required', 'email', 'max:190'],
            'mail_from_name' => ['required', 'string', 'max:120'],
        ]);

        $envUpdates = [
            'MAIL_MAILER' => $validated['mail_mailer'],
            'MAIL_HOST' => $validated['mail_host'] ?? '127.0.0.1',
            'MAIL_PORT' => $validated['mail_port'] ?? '587',
            'MAIL_ENCRYPTION' => $validated['mail_encryption'] === 'none' ? 'null' : ($validated['mail_encryption'] ?? 'tls'),
            'MAIL_FROM_ADDRESS' => '"'.$validated['mail_from_address'].'"',
            'MAIL_FROM_NAME' => '"'.$validated['mail_from_name'].'"',
        ];

        if (! empty($validated['mail_username'])) {
            $envUpdates['MAIL_USERNAME'] = '"'.$validated['mail_username'].'"';
        }

        if (! empty($validated['mail_password'])) {
            $envUpdates['MAIL_PASSWORD'] = '"'.$validated['mail_password'].'"';
        }

        $this->updateEnvFile($envUpdates);

        try {
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            Log::warning('Config clear after SMTP update: '.$e->getMessage());
        }

        ActivityLog::record(
            entityType: 'settings',
            entityId: 0,
            action: 'SMTP_CONFIG_UPDATED',
            fromStatus: null,
            toStatus: null,
            remarks: "Super Admin updated outgoing mail server settings (Mailer: {$validated['mail_mailer']}).",
            userId: Auth::id()
        );

        return redirect()->route('admin.mail-settings.index')
            ->with('success', 'Outgoing mail server (SMTP) configuration saved successfully!');
    }

    /**
     * Dispatch a test email to verify outgoing SMTP connectivity.
     */
    public function sendTest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:190'],
        ]);

        $recipient = trim($validated['test_email']);

        try {
            Mail::raw(
                'This is a test escalation notification from NTTN Portal ('.config('app.name', 'NTTN Portal').").\n\n".
                "If you received this message, your outgoing mail configuration is operating correctly!\n".
                'Timestamp: '.now()->format('Y-m-d H:i:s')." UTC\n".
                'Server Host: '.gethostname()."\n".
                'Mailer Driver: '.config('mail.default'),
                function ($message) use ($recipient) {
                    $message->to($recipient)
                        ->subject('[TEST MAIL] NTTN Portal Outgoing Mail Verification');
                }
            );

            return redirect()->route('admin.mail-settings.index')
                ->with('success', "Test email successfully dispatched to {$recipient}! Please check inbox/spam.");
        } catch (\Throwable $e) {
            Log::error("Test email dispatch failure to {$recipient}: ".$e->getMessage());

            return redirect()->route('admin.mail-settings.index')
                ->with('error', 'Failed delivering test email: '.$e->getMessage());
        }
    }

    /**
     * Update key-value pairs in the .env file safely.
     */
    protected function updateEnvFile(array $values): void
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            return;
        }

        $content = File::get($envPath);

        foreach ($values as $key => $val) {
            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$val}", $content);
            } else {
                $content .= "\n{$key}={$val}";
            }
        }

        File::put($envPath, $content);
    }
}
