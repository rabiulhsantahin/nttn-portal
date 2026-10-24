<?php

namespace App\Services;

use App\Mail\DatabaseBackupMail;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PDO;
use Symfony\Component\Process\Process;

class BackupService
{
    protected string $backupDir;

    protected string $settingsFile;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        $this->settingsFile = storage_path('app/backup_settings.json');

        if (! File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Get backup settings.
     */
    public function getSettings(): array
    {
        if (File::exists($this->settingsFile)) {
            $data = json_decode(File::get($this->settingsFile), true);
            if (is_array($data)) {
                return array_merge($this->defaultSettings(), $data);
            }
        }

        return $this->defaultSettings();
    }

    /**
     * Update backup settings.
     */
    public function saveSettings(array $settings): array
    {
        $current = $this->getSettings();
        $merged = array_merge($current, $settings);
        File::put($this->settingsFile, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $merged;
    }

    protected function defaultSettings(): array
    {
        $adminEmail = User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->value('email') ?? 'admin@nttn.test';

        return [
            'email' => $adminEmail,
            'enabled' => true,
            'keep_days' => 14,
            'last_backup_at' => null,
            'last_backup_file' => null,
            'last_status' => null,
            'last_error' => null,
        ];
    }

    /**
     * Create a complete SQL database dump and gzip it.
     */
    public function createBackup(): array
    {
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $appName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', config('app.name', 'nttn_portal'));
        $baseFilename = "{$appName}_db_backup_{$timestamp}";
        $sqlPath = "{$this->backupDir}/{$baseFilename}.sql";
        $gzPath = "{$this->backupDir}/{$baseFilename}.sql.gz";

        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        $tablesCount = 0;

        if (in_array($connection, ['mysql', 'mariadb'])) {
            $tablesCount = $this->dumpMySql($dbConfig, $sqlPath);
        } elseif ($connection === 'sqlite') {
            $tablesCount = $this->dumpSqlite($dbConfig, $sqlPath);
        } else {
            // Generic PDO fallback
            $tablesCount = $this->dumpPdoFallback($sqlPath);
        }

        // Compress SQL file with gzip
        if (file_exists($sqlPath)) {
            if (function_exists('gzencode')) {
                $sqlContent = file_get_contents($sqlPath);
                $compressed = gzencode($sqlContent, 9);
                file_put_contents($gzPath, $compressed);
                @unlink($sqlPath);
                $finalPath = $gzPath;
                $finalFilename = "{$baseFilename}.sql.gz";
            } else {
                $finalPath = $sqlPath;
                $finalFilename = "{$baseFilename}.sql";
            }
        } else {
            throw new \RuntimeException("Database dump file was not generated at {$sqlPath}");
        }

        $size = filesize($finalPath);

        // Update settings metadata
        $this->saveSettings([
            'last_backup_at' => Carbon::now()->toDateTimeString(),
            'last_backup_file' => $finalFilename,
            'last_status' => 'SUCCESS',
            'last_error' => null,
        ]);

        // Cleanup backups older than retention days
        $this->pruneOldBackups();

        return [
            'path' => $finalPath,
            'filename' => $finalFilename,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'tables_count' => $tablesCount,
            'created_at' => Carbon::now(),
            'database' => $dbConfig['database'] ?? $connection,
        ];
    }

    /**
     * Dump MySQL or MariaDB database.
     */
    protected function dumpMySql(array $config, string $sqlPath): int
    {
        $dumpBinary = $this->resolveDumpBinary();

        if ($dumpBinary) {
            $host = $config['host'] ?? '127.0.0.1';
            $port = (string) ($config['port'] ?? 3306);
            $user = $config['username'] ?? 'root';
            $password = $config['password'] ?? '';
            $database = $config['database'];
            $socket = $config['unix_socket'] ?? '';

            $args = [
                $dumpBinary,
                '--single-transaction',
                '--quick',
                '--routines',
                '--triggers',
                '--add-drop-table',
                '--hex-blob',
                '--default-character-set=utf8mb4',
                "-u{$user}",
            ];

            if (! empty($socket) && file_exists($socket)) {
                $args[] = "--socket={$socket}";
            } else {
                $args[] = "-h{$host}";
                $args[] = "-P{$port}";
            }

            if ($password !== '') {
                $args[] = "-p{$password}";
            }

            $args[] = $database;

            $process = new Process($args);
            $process->setTimeout(300);
            $process->run();

            if ($process->isSuccessful() && strlen($process->getOutput()) > 50) {
                file_put_contents($sqlPath, $process->getOutput());

                return $this->countTables();
            }

            Log::warning('mysqldump binary failed or returned empty output, falling back to PDO dumper: '.$process->getErrorOutput());
        }

        // Fallback to pure PDO dumper
        return $this->dumpPdoFallback($sqlPath);
    }

    /**
     * Dump SQLite database.
     */
    protected function dumpSqlite(array $config, string $sqlPath): int
    {
        $database = $config['database'];

        if ($database === ':memory:') {
            return $this->dumpPdoFallback($sqlPath);
        }

        if (file_exists($database)) {
            // Try sqlite3 CLI
            $process = new Process(['sqlite3', $database, '.dump']);
            $process->run();

            if ($process->isSuccessful() && strlen($process->getOutput()) > 20) {
                file_put_contents($sqlPath, $process->getOutput());

                return $this->countTables();
            }

            // Fallback: Copy raw file as SQL script header
            $header = '-- SQLite Database Backup: '.basename($database)."\n";
            file_put_contents($sqlPath, $header.file_get_contents($database));

            return $this->countTables();
        }

        return $this->dumpPdoFallback($sqlPath);
    }

    /**
     * Pure PHP PDO Schema & Data Exporter (zero external dependency fallback).
     */
    protected function dumpPdoFallback(string $sqlPath): int
    {
        $pdo = DB::connection()->getPdo();
        $connection = config('database.default');

        $out = fopen($sqlPath, 'w');
        fwrite($out, "-- ========================================================\n");
        fwrite($out, "-- NTTN Portal Automated Disaster Recovery Database Backup\n");
        fwrite($out, '-- Date: '.date('Y-m-d H:i:s')." UTC\n");
        fwrite($out, '-- Driver: '.$connection."\n");
        fwrite($out, "-- ========================================================\n\n");
        if ($connection === 'sqlite') {
            fwrite($out, "PRAGMA foreign_keys = OFF;\n\n");
        } else {
            fwrite($out, "SET FOREIGN_KEY_CHECKS=0;\n\n");
        }

        $tables = [];
        if ($connection === 'sqlite') {
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $stmt = $pdo->query('SHOW TABLES');
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        foreach ($tables as $table) {
            fwrite($out, "-- --------------------------------------------------------\n");
            fwrite($out, "-- Table structure for `{$table}`\n");
            fwrite($out, "-- --------------------------------------------------------\n");
            fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n");

            if ($connection === 'sqlite') {
                $createStmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$table}'");
                $createSql = $createStmt->fetchColumn();
                fwrite($out, $createSql.";\n\n");
            } else {
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $row = $createStmt->fetch(PDO::FETCH_ASSOC);
                $createSql = $row['Create Table'] ?? null;
                if ($createSql) {
                    fwrite($out, $createSql.";\n\n");
                }
            }

            // Dump data
            $rowsStmt = $pdo->query("SELECT * FROM `{$table}`");
            $rowCount = 0;

            while ($row = $rowsStmt->fetch(PDO::FETCH_ASSOC)) {
                if ($rowCount === 0) {
                    fwrite($out, "-- Dumping data for table `{$table}`\n");
                }

                $columns = array_map(fn ($c) => "`{$c}`", array_keys($row));
                $values = array_map(function ($val) use ($pdo) {
                    if (is_null($val)) {
                        return 'NULL';
                    }

                    return $pdo->quote((string) $val);
                }, array_values($row));

                fwrite($out, "INSERT INTO `{$table}` (".implode(', ', $columns).') VALUES ('.implode(', ', $values).");\n");
                $rowCount++;
            }

            fwrite($out, "\n");
        }

        if ($connection === 'sqlite') {
            fwrite($out, "PRAGMA foreign_keys = ON;\n");
        } else {
            fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
        }
        fwrite($out, '-- Dump completed on '.date('Y-m-d H:i:s')." UTC\n");
        fclose($out);

        return count($tables);
    }

    /**
     * Send database backup via Email.
     */
    public function sendBackupEmail(?string $recipient = null, ?string $specificBackupFile = null): array
    {
        $settings = $this->getSettings();
        $targetEmail = $recipient ?: ($settings['email'] ?? 'admin@nttn.test');

        if (empty($targetEmail)) {
            throw new \InvalidArgumentException('No valid recipient email address specified for backup delivery.');
        }

        // Generate a new backup or use existing
        if ($specificBackupFile && file_exists("{$this->backupDir}/{$specificBackupFile}")) {
            $filePath = "{$this->backupDir}/{$specificBackupFile}";
            $filename = $specificBackupFile;
            $size = filesize($filePath);
            $backup = [
                'path' => $filePath,
                'filename' => $filename,
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'tables_count' => $this->countTables(),
                'created_at' => Carbon::createFromTimestamp(filemtime($filePath)),
                'database' => config('database.connections.'.config('database.default').'.database', 'nttn_portal'),
            ];
        } else {
            $backup = $this->createBackup();
        }

        try {
            Mail::to($targetEmail)->send(new DatabaseBackupMail($backup));

            ActivityLog::record(
                entityType: 'backup',
                entityId: 0,
                action: 'DATABASE_BACKUP_SENT_VIA_EMAIL',
                fromStatus: null,
                toStatus: 'DELIVERED',
                remarks: "Database backup {$backup['filename']} ({$backup['size_formatted']}) dispatched to {$targetEmail}.",
                userId: Auth::id()
            );

            return [
                'success' => true,
                'message' => "Backup {$backup['filename']} successfully delivered to {$targetEmail}.",
                'backup' => $backup,
                'recipient' => $targetEmail,
            ];
        } catch (\Throwable $e) {
            Log::error("Failed sending database backup email to {$targetEmail}: ".$e->getMessage());

            $this->saveSettings([
                'last_status' => 'EMAIL_FAILED',
                'last_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * List all backup archives in storage.
     */
    public function listBackups(): array
    {
        if (! File::exists($this->backupDir)) {
            return [];
        }

        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['gz', 'sql', 'zip'])) {
                continue;
            }

            $size = $file->getSize();
            $mtime = $file->getMTime();

            $backups[] = [
                'filename' => $file->getFilename(),
                'path' => $file->getRealPath(),
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'created_at' => Carbon::createFromTimestamp($mtime),
                'is_compressed' => ($ext === 'gz' || $ext === 'zip'),
            ];
        }

        // Sort latest first
        usort($backups, fn ($a, $b) => $b['created_at']->timestamp <=> $a['created_at']->timestamp);

        return $backups;
    }

    /**
     * Delete an individual backup archive.
     */
    public function deleteBackup(string $filename): bool
    {
        $safeFilename = basename($filename);
        $fullPath = "{$this->backupDir}/{$safeFilename}";

        if (File::exists($fullPath)) {
            File::delete($fullPath);

            ActivityLog::record(
                entityType: 'backup',
                entityId: 0,
                action: 'BACKUP_FILE_DELETED',
                fromStatus: null,
                toStatus: null,
                remarks: "Super Admin deleted backup file {$safeFilename}.",
                userId: Auth::id()
            );

            return true;
        }

        return false;
    }

    /**
     * Prune backups older than retention threshold.
     */
    public function pruneOldBackups(): int
    {
        $settings = $this->getSettings();
        $keepDays = (int) ($settings['keep_days'] ?? 14);
        if ($keepDays <= 0) {
            return 0;
        }

        $cutoff = Carbon::now()->subDays($keepDays)->timestamp;
        $deleted = 0;

        foreach ($this->listBackups() as $b) {
            if ($b['created_at']->timestamp < $cutoff) {
                @unlink($b['path']);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Restore database from a backup file (.sql or .sql.gz).
     *
     * @param  string  $sourcePath  Path to .sql or .sql.gz file
     * @return array{
     *     success: bool,
     *     tables_count: int,
     *     message: string,
     *     restored_at: Carbon,
     *     duration_seconds: float
     * }
     */
    public function restoreBackup(string $sourcePath): array
    {
        if (! file_exists($sourcePath) || ! is_readable($sourcePath)) {
            throw new \InvalidArgumentException("Backup file does not exist or is not readable: {$sourcePath}");
        }

        $startTime = microtime(true);
        $tempSqlPath = null;
        $isCompressed = $this->isGzipped($sourcePath);

        if ($isCompressed) {
            $tempSqlPath = storage_path('app/backups/restore_decompressed_'.uniqid().'.sql');
            $this->decompressGzip($sourcePath, $tempSqlPath);
            $effectiveSqlPath = $tempSqlPath;
        } else {
            $effectiveSqlPath = $sourcePath;
        }

        if (! file_exists($effectiveSqlPath) || filesize($effectiveSqlPath) < 10) {
            if ($tempSqlPath && file_exists($tempSqlPath)) {
                @unlink($tempSqlPath);
            }
            throw new \RuntimeException('Restoration failed: The SQL script is empty or invalid.');
        }

        try {
            $connection = config('database.default');
            $dbConfig = config("database.connections.{$connection}", []);

            if (in_array($connection, ['mysql', 'mariadb'])) {
                $this->restoreMySql($dbConfig, $effectiveSqlPath);
            } elseif ($connection === 'sqlite') {
                $this->restoreSqlite($dbConfig, $effectiveSqlPath);
            } else {
                $this->restoreViaPdo($effectiveSqlPath);
            }
        } finally {
            if ($tempSqlPath && file_exists($tempSqlPath)) {
                @unlink($tempSqlPath);
            }
        }

        $duration = round(microtime(true) - $startTime, 2);
        $tablesCount = $this->countTables();

        try {
            Artisan::call('cache:clear');
        } catch (\Throwable) {
            // cache clear is non-fatal
        }

        return [
            'success' => true,
            'tables_count' => $tablesCount,
            'message' => "Database restored successfully in {$duration}s. {$tablesCount} tables verified.",
            'restored_at' => Carbon::now(),
            'duration_seconds' => $duration,
        ];
    }

    protected function isGzipped(string $filePath): bool
    {
        if (str_ends_with(strtolower($filePath), '.gz')) {
            return true;
        }

        $handle = @fopen($filePath, 'rb');
        if (! $handle) {
            return false;
        }
        $header = fread($handle, 2);
        fclose($handle);

        return $header === "\x1f\x8b";
    }

    protected function decompressGzip(string $gzPath, string $destPath): void
    {
        $gz = @gzopen($gzPath, 'rb');
        if (! $gz) {
            throw new \RuntimeException("Unable to open gzip archive: {$gzPath}");
        }

        $dest = @fopen($destPath, 'wb');
        if (! $dest) {
            gzclose($gz);
            throw new \RuntimeException("Unable to create temporary decompressed file: {$destPath}");
        }

        while (! gzeof($gz)) {
            $chunk = gzread($gz, 65536);
            if ($chunk !== false && strlen($chunk) > 0) {
                fwrite($dest, $chunk);
            }
        }

        gzclose($gz);
        fclose($dest);
    }

    protected function restoreMySql(array $config, string $sqlPath): void
    {
        $mysqlBinary = $this->resolveMysqlClientBinary();

        if ($mysqlBinary) {
            $host = $config['host'] ?? '127.0.0.1';
            $port = (string) ($config['port'] ?? 3306);
            $user = $config['username'] ?? 'root';
            $password = $config['password'] ?? '';
            $database = $config['database'];
            $socket = $config['unix_socket'] ?? '';

            $args = [
                $mysqlBinary,
                '--default-character-set=utf8mb4',
                "-u{$user}",
            ];

            if (! empty($socket) && file_exists($socket)) {
                $args[] = "--socket={$socket}";
            } else {
                $args[] = "-h{$host}";
                $args[] = "-P{$port}";
            }

            if ($password !== '') {
                $args[] = "-p{$password}";
            }

            $args[] = $database;

            $process = new Process($args);
            $process->setTimeout(600);
            $handle = fopen($sqlPath, 'r');
            $process->setInput($handle);
            $process->run();
            if (is_resource($handle)) {
                fclose($handle);
            }

            if ($process->isSuccessful()) {
                return;
            }

            Log::warning('CLI restore failed, falling back to PDO executor: '.$process->getErrorOutput());
        }

        $this->restoreViaPdo($sqlPath);
    }

    protected function restoreSqlite(array $config, string $sqlPath): void
    {
        $database = $config['database'];

        $header = '';
        $f = @fopen($sqlPath, 'rb');
        if ($f) {
            $header = fread($f, 16);
            fclose($f);
        }

        if (str_starts_with($header, 'SQLite format 3') && $database !== ':memory:') {
            DB::disconnect();
            copy($sqlPath, $database);
            DB::reconnect();

            return;
        }

        $this->restoreViaPdo($sqlPath);
    }

    protected function restoreViaPdo(string $sqlPath): void
    {
        $content = file_get_contents($sqlPath);
        if ($content === false || strlen(trim($content)) === 0) {
            throw new \RuntimeException('SQL backup file is empty.');
        }

        $connection = config('database.default');

        if ($connection === 'sqlite') {
            $cleaned = preg_replace('/SET\s+(FOREIGN_KEY_CHECKS|UNIQUE_CHECKS)\s*=\s*[01]\s*;/i', '', $content);
            DB::unprepared("PRAGMA foreign_keys = OFF;\n".$cleaned."\nPRAGMA foreign_keys = ON;");
        } else {
            $cleaned = preg_replace('/PRAGMA\s+foreign_keys\s*=\s*(OFF|ON)\s*;/i', '', $content);
            DB::unprepared("SET FOREIGN_KEY_CHECKS=0;\n".$cleaned."\nSET FOREIGN_KEY_CHECKS=1;");
        }
    }

    protected function resolveMysqlClientBinary(): ?string
    {
        foreach (['mariadb', 'mysql', '/usr/bin/mariadb', '/usr/bin/mysql'] as $binary) {
            $p = new Process([PHP_OS_FAMILY === 'Windows' ? 'where.exe' : 'which', $binary]);
            $p->run();
            if ($p->isSuccessful() && ! empty(trim($p->getOutput()))) {
                return trim(explode("\n", trim($p->getOutput()))[0]);
            }
        }

        return null;
    }

    protected function resolveDumpBinary(): ?string
    {
        foreach (['mariadb-dump', 'mysqldump', '/usr/bin/mariadb-dump', '/usr/bin/mysqldump'] as $binary) {
            $p = new Process([PHP_OS_FAMILY === 'Windows' ? 'where.exe' : 'which', $binary]);
            $p->run();
            if ($p->isSuccessful() && ! empty(trim($p->getOutput()))) {
                return trim(explode("\n", trim($p->getOutput()))[0]);
            }
        }

        return null;
    }

    protected function countTables(): int
    {
        try {
            $conn = config('database.default');
            if ($conn === 'sqlite') {
                return (int) DB::scalar("SELECT count(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            }

            return (int) count(DB::select('SHOW TABLES'));
        } catch (\Throwable) {
            return 0;
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
