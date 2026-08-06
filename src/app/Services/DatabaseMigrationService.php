<?php

namespace App\Services;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseMigrationService
{
    /**
     * Migration basenames that alter or drop primary keys or otherwise require
     * operator opt-in. These must be applied manually via php artisan migrate.
     */
    public const DESTRUCTIVE_MIGRATIONS = [
        '2025_01_01_163927_convert_id_to_guid_in_users_table',
    ];

    public const ADVISORY_LOCK_NAME = 'yap_migrate';

    private Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
    }

    public function pendingMigrationNames(): array
    {
        $files = $this->migrator->getMigrationFiles($this->migrationPath());
        $ran = $this->migrator->getRepository()->getRan();

        return array_values(array_diff(array_keys($files), $ran));
    }

    public function pendingDestructiveMigrationNames(): array
    {
        return array_values(array_intersect(
            $this->pendingMigrationNames(),
            self::DESTRUCTIVE_MIGRATIONS
        ));
    }

    public function pendingSafeMigrationNames(): array
    {
        return array_values(array_diff(
            $this->pendingMigrationNames(),
            self::DESTRUCTIVE_MIGRATIONS
        ));
    }

    public function runSafePendingMigrations(): void
    {
        $safe = $this->pendingSafeMigrationNames();
        if (empty($safe)) {
            return;
        }

        if (!$this->acquireAdvisoryLock()) {
            return;
        }

        try {
            foreach ($safe as $migration) {
                Log::warning("Auto-running database migration: {$migration}");
                $path = $this->migrationPath() . DIRECTORY_SEPARATOR . $migration . '.php';
                $this->migrator->run([$path], ['force' => true]);
            }
        } finally {
            $this->releaseAdvisoryLock();
        }
    }

    private function migrationPath(): string
    {
        return database_path('migrations');
    }

    private function acquireAdvisoryLock(): bool
    {
        $result = DB::selectOne('SELECT GET_LOCK(?, 0) as locked', [self::ADVISORY_LOCK_NAME]);

        return (int) $result->locked === 1;
    }

    private function releaseAdvisoryLock(): void
    {
        DB::select('SELECT RELEASE_LOCK(?)', [self::ADVISORY_LOCK_NAME]);
    }
}
