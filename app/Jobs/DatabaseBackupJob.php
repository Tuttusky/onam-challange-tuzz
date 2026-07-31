<?php

namespace App\Jobs;

use App\Models\Backup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class DatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $disk = 'local',
        public ?int $createdBy = null
    ) {}

    public function handle(): void
    {
        $connection = Config::get('database.default');
        $driver = Config::get("database.connections.{$connection}.driver");
        $timestamp = now()->format('Y-m-d_His');
        $filename = "backup_{$connection}_{$timestamp}.sql";
        $directory = storage_path('app/backups');

        File::ensureDirectoryExists($directory);

        $absolutePath = "{$directory}/{$filename}";

        match ($driver) {
            'mysql', 'mariadb' => $this->backupMysql($connection, $absolutePath),
            'pgsql' => $this->backupPostgres($connection, $absolutePath),
            'sqlite' => $this->backupSqlite($connection, $absolutePath),
            default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
        };

        $size = File::exists($absolutePath) ? File::size($absolutePath) : 0;

        Backup::query()->create([
            'filename' => $filename,
            'disk' => $this->disk,
            'size' => $size,
            'created_by' => $this->createdBy,
        ]);

        Log::info('Database backup completed.', [
            'filename' => $filename,
            'size' => $size,
            'driver' => $driver,
        ]);
    }

    protected function backupMysql(string $connection, string $absolutePath): void
    {
        $config = Config::get("database.connections.{$connection}");

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s > %s',
            escapeshellarg((string) ($config['host'] ?? '127.0.0.1')),
            escapeshellarg((string) ($config['port'] ?? '3306')),
            escapeshellarg((string) ($config['username'] ?? 'root')),
            escapeshellarg((string) ($config['database'] ?? '')),
            escapeshellarg($absolutePath)
        );

        if (! empty($config['password'])) {
            putenv('MYSQL_PWD='.$config['password']);
        }

        $result = Process::run($command);

        if (! $result->successful()) {
            throw new \RuntimeException('MySQL backup failed: '.$result->errorOutput());
        }
    }

    protected function backupPostgres(string $connection, string $absolutePath): void
    {
        $config = Config::get("database.connections.{$connection}");

        $result = Process::env([
            'PGPASSWORD' => (string) ($config['password'] ?? ''),
        ])->run(sprintf(
            'pg_dump --host=%s --port=%s --username=%s --dbname=%s --file=%s',
            escapeshellarg((string) ($config['host'] ?? '127.0.0.1')),
            escapeshellarg((string) ($config['port'] ?? '5432')),
            escapeshellarg((string) ($config['username'] ?? 'postgres')),
            escapeshellarg((string) ($config['database'] ?? '')),
            escapeshellarg($absolutePath)
        ));

        if (! $result->successful()) {
            throw new \RuntimeException('PostgreSQL backup failed: '.$result->errorOutput());
        }
    }

    protected function backupSqlite(string $connection, string $absolutePath): void
    {
        $databasePath = Config::get("database.connections.{$connection}.database");

        if (! is_string($databasePath) || ! File::exists($databasePath)) {
            throw new \RuntimeException('SQLite database file not found.');
        }

        File::copy($databasePath, $absolutePath);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Database backup job failed.', [
            'disk' => $this->disk,
            'created_by' => $this->createdBy,
            'error' => $exception?->getMessage(),
        ]);
    }
}
