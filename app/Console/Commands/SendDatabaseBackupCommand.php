<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class SendDatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database
                            {--email= : Override recipient email address}
                            {--no-email : Only generate backup on disk without sending email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate full database dump, compress with Gzip, and dispatch via email for daily disaster recovery';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info('Starting NTTN Portal database disaster recovery backup...');

        $settings = $backupService->getSettings();

        if (! ($settings['enabled'] ?? true) && ! $this->option('email')) {
            $this->warn('Automated database backups are currently disabled in settings.');

            return self::SUCCESS;
        }

        try {
            if ($this->option('no-email')) {
                $backup = $backupService->createBackup();
                $this->info("Backup generated successfully on disk: {$backup['filename']} ({$backup['size_formatted']})");

                return self::SUCCESS;
            }

            $recipient = $this->option('email') ?: ($settings['email'] ?? null);
            $result = $backupService->sendBackupEmail($recipient);

            $this->info("✓ {$result['message']}");
            $this->table(
                ['Property', 'Value'],
                [
                    ['File Name', $result['backup']['filename']],
                    ['Archive Size', $result['backup']['size_formatted']],
                    ['Tables Count', $result['backup']['tables_count']],
                    ['Recipient', $result['recipient']],
                    ['Timestamp', $result['backup']['created_at']->toDateTimeString()],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
