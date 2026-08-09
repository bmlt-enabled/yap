<?php

namespace App\Services;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Log;

class DatabaseMigrationService
{
    /**
     * Migration basenames that alter or drop primary keys or otherwise require
     * operator opt-in. These must be applied manually via php artisan migrate.
     *
     * @var list<string>
     */
    public const DESTRUCTIVE_MIGRATIONS = [];

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

    /**
     * Run pending safe migrations. Caller must hold the yap-db-migrations advisory lock.
     */
    public function runSafePendingMigrations(): void
    {
        $safe = $this->pendingSafeMigrationNames();
        if (empty($safe)) {
            return;
        }

        foreach ($safe as $migration) {
            Log::warning("Auto-running database migration: {$migration}");
            $path = $this->migrationPath() . DIRECTORY_SEPARATOR . $migration . '.php';
            $this->migrator->run([$path], ['force' => true]);
        }
    }

    private function migrationPath(): string
    {
        return database_path('migrations');
    }
}
