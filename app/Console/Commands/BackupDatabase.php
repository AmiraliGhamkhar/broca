<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Dumps the MySQL database to a gzip in storage/app/backups and prunes old
 * files. This is the LOCAL half of the backup strategy — the off-box copy
 * (rclone/rsync to object storage or another host) is a deployment cron
 * documented in docs/RUNBOOK.md. A backup that never leaves the database
 * host is not a backup.
 */
class BackupDatabase extends Command
{
    protected $signature = 'broca:backup-database {--days=14 : Prune backups older than N days}';

    protected $description = 'Dump the MySQL database to storage/app/backups (gzip) and prune old backups';

    public function handle(): int
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver !== 'mysql') {
            $this->warn("Unsupported driver '{$driver}' — broca:backup-database supports MySQL only.");

            return self::INVALID;
        }

        if (! $this->mysqldumpAvailable()) {
            $this->error('mysqldump binary not found on PATH.');

            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0770, true);
        }

        $filename = 'broca-'.now()->format('Ymd-His').'.sql';
        $sqlPath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        $config = config("database.connections.{$connection}");

        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');

        // Never fall back to a root login: a missing username is a
        // configuration error and must fail loudly, not silently attempt
        // root without a password (audit finding, 2026-08-29).
        if ($username === '') {
            $this->error('DB username is not configured — refusing to fall back to root.');

            return self::FAILURE;
        }

        if ($username === 'root' && $password === '') {
            $this->warn('Connecting as root without a password — development-only posture.');
        }

        // MYSQL_PWD keeps the password out of the process argument list.
        // --single-transaction: consistent InnoDB snapshot without table locks.
        $dump = new Process(
            [
                'mysqldump',
                '--host='.($config['host'] ?? '127.0.0.1'),
                '--port='.(string) ($config['port'] ?? 3306),
                '--user='.$username,
                '--single-transaction',
                '--routines',
                '--triggers',
                $config['database'],
            ],
            null,
            ['MYSQL_PWD' => $password],
            null,
            600, // 10 minute timeout — large databases need the headroom
        );

        // Stream chunks to disk so the dump never lives fully in memory.
        // The file contains user PII — owner-only permissions (gzip
        // preserves the mode of the file it compresses).
        $dump->run(function (string $type, string $buffer) use ($sqlPath): void {
            if ($type === Process::OUT) {
                file_put_contents($sqlPath, $buffer, FILE_APPEND);
                @chmod($sqlPath, 0600);
            }
        });

        if (! $dump->isSuccessful() || ! is_file($sqlPath) || filesize($sqlPath) === 0) {
            @unlink($sqlPath);
            $this->error('Backup failed: '.mb_substr($dump->getErrorOutput() ?: 'empty dump', 0, 500));

            return self::FAILURE;
        }

        $gzip = new Process(['gzip', '-f', $sqlPath], null, null, null, 600);
        $gzip->run();

        if (! $gzip->isSuccessful()) {
            $this->error('gzip failed: '.mb_substr($gzip->getErrorOutput(), 0, 300));

            return self::FAILURE;
        }

        $gzPath = $sqlPath.'.gz';
        @chmod($gzPath, 0600); // gzip preserves the source mode; enforce anyway
        $this->info('Backup written to storage/app/backups/'.basename($gzPath).' ('.round(filesize($gzPath) / 1024, 1).' KB).');

        $this->prune($backupDir, (int) $this->option('days'));

        // Remind the operator: a backup on the same disk as the database is
        // only half a strategy.
        $this->line('NOTE: copy storage/app/backups off-box (see docs/RUNBOOK.md).');

        return self::SUCCESS;
    }

    private function mysqldumpAvailable(): bool
    {
        $check = new Process(['which', 'mysqldump']);
        $check->run();

        return $check->isSuccessful();
    }

    private function prune(string $backupDir, int $days): void
    {
        $cutoff = now()->subDays($days)->getTimestamp();

        foreach (glob($backupDir.DIRECTORY_SEPARATOR.'broca-*.sql.gz') ?: [] as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $this->line('Pruned old backup '.basename($file).'.');
            }
        }
    }
}
