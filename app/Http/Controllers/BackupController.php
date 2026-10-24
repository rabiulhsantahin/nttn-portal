<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(protected BackupService $backupService) {}

    /**
     * Display disaster recovery and backup management dashboard.
     */
    public function index(): View
    {
        $settings = $this->backupService->getSettings();
        $backups = $this->backupService->listBackups();

        $totalStorageBytes = array_sum(array_column($backups, 'size'));
        $totalStorageFormatted = $this->formatBytes($totalStorageBytes);

        $mailConfig = [
            'mailer' => config('mail.default'),
            'from' => config('mail.from.address'),
            'host' => config('mail.mailers.smtp.host'),
        ];

        return view('backups.index', compact('settings', 'backups', 'totalStorageFormatted', 'mailConfig'));
    }

    /**
     * Create an immediate backup (with optional direct download).
     */
    public function store(Request $request): RedirectResponse|BinaryFileResponse
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only Super Administrator can trigger backups.');
        }

        try {
            $backup = $this->backupService->createBackup();

            if ($request->boolean('download')) {
                return response()->download($backup['path'], $backup['filename'], [
                    'Content-Type' => 'application/gzip',
                ]);
            }

            return redirect()->route('backups.index')
                ->with('success', "Database backup '{$backup['filename']}' ({$backup['size_formatted']}) was created successfully.");
        } catch (\Throwable $e) {
            return redirect()->route('backups.index')
                ->with('error', 'Backup generation failed: '.$e->getMessage());
        }
    }

    /**
     * Trigger backup and dispatch via Email immediately.
     */
    public function sendEmail(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only Super Administrator can dispatch backups.');
        }

        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:190'],
            'filename' => ['nullable', 'string'],
        ]);

        try {
            $result = $this->backupService->sendBackupEmail(
                $validated['email'] ?? null,
                $validated['filename'] ?? null
            );

            return redirect()->route('backups.index')
                ->with('success', $result['message']);
        } catch (\Throwable $e) {
            return redirect()->route('backups.index')
                ->with('error', 'Failed delivering backup email: '.$e->getMessage());
        }
    }

    /**
     * Download a specific backup file.
     */
    public function download(string $filename): BinaryFileResponse
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $safeName = basename($filename);
        $fullPath = storage_path("app/backups/{$safeName}");

        if (! file_exists($fullPath)) {
            abort(404, "Backup archive '{$safeName}' not found on server.");
        }

        return response()->download($fullPath, $safeName, [
            'Content-Type' => str_ends_with($safeName, '.gz') ? 'application/gzip' : 'application/sql',
        ]);
    }

    /**
     * Delete an individual backup file from disk.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $safeName = basename($filename);
        $deleted = $this->backupService->deleteBackup($safeName);

        if ($deleted) {
            return redirect()->route('backups.index')
                ->with('success', "Backup file '{$safeName}' deleted from server.");
        }

        return redirect()->route('backups.index')
            ->with('error', "Could not find backup file '{$safeName}'.");
    }

    /**
     * Update backup schedule and notification settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'enabled' => ['nullable', 'boolean'],
            'keep_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $this->backupService->saveSettings([
            'email' => $validated['email'],
            'enabled' => $request->boolean('enabled'),
            'keep_days' => (int) $validated['keep_days'],
        ]);

        return redirect()->route('backups.index')
            ->with('success', 'Disaster recovery and backup settings have been updated.');
    }

    /**
     * Restore database from an uploaded file or an existing server archive.
     */
    public function restore(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only Super Administrator can restore database.');
        }

        $request->validate([
            'backup_file' => ['nullable', 'file', 'max:102400'],
            'filename' => ['nullable', 'string'],
            'confirm_restore' => ['required'],
        ], [
            'confirm_restore.required' => 'You must check the confirmation box to authorize restoring and overwriting the database.',
        ]);

        $uploadedFile = $request->file('backup_file');
        $archiveFilename = $request->input('filename');

        if (! $uploadedFile && ! $archiveFilename) {
            return redirect()->route('backups.index')
                ->with('error', 'Please choose a backup file to upload (.sql or .sql.gz) or select an existing archive.');
        }

        $tempPath = null;
        $originLabel = '';

        try {
            if ($uploadedFile) {
                $ext = strtolower($uploadedFile->getClientOriginalExtension());
                $origName = $uploadedFile->getClientOriginalName();

                if (! in_array($ext, ['sql', 'gz', 'txt', 'bin'])) {
                    return redirect()->route('backups.index')
                        ->with('error', "Invalid backup file type (.{$ext}). Please upload a valid .sql or .sql.gz database dump.");
                }

                $safeName = 'upload_restore_'.time().'_'.preg_replace('/[^A-Za-z0-9_.-]/', '_', $origName);
                $uploadedFile->move(storage_path('app/backups'), $safeName);
                $targetPath = storage_path("app/backups/{$safeName}");
                $tempPath = $targetPath;
                $originLabel = "uploaded file '{$origName}' (".$this->formatBytes(filesize($targetPath)).')';
            } else {
                $safeName = basename($archiveFilename);
                $targetPath = storage_path("app/backups/{$safeName}");
                if (! file_exists($targetPath)) {
                    return redirect()->route('backups.index')
                        ->with('error', "Backup archive '{$safeName}' not found on server.");
                }
                $originLabel = "server archive '{$safeName}'";
            }

            $result = $this->backupService->restoreBackup($targetPath);

            ActivityLog::record(
                entityType: 'backup',
                entityId: 0,
                action: 'DATABASE_RESTORED_FROM_BACKUP',
                fromStatus: null,
                toStatus: 'RESTORED',
                remarks: "Full database restored from {$originLabel}. Result: {$result['message']}.",
                userId: $user->id
            );

            return redirect()->route('backups.index')
                ->with('success', "Database successfully restored from {$originLabel}! {$result['tables_count']} tables verified.");
        } catch (\Throwable $e) {
            Log::error('Database restore error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->route('backups.index')
                ->with('error', 'Database restore failed: '.$e->getMessage());
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
