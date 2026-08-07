<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseMigrations
{
    /**
     * Update this when adding a new migration so the middleware knows the schema
     * is current and can skip calling Artisan on every request.
     */
    private const LATEST_MIGRATION = '2026_01_03_000000_create_chat_sessions_table';

    private const MIGRATION_LOCK_NAME = 'yap-db-migrations';

    public function __construct(
        private SettingsService $settings,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$this->isDatabaseConfigured()) {
            return $next($request);
        }

        if ($this->migrationsShouldRun()) {
            try {
                ini_set('max_execution_time', '600');
                DB::select('SELECT GET_LOCK(?, 600)', [self::MIGRATION_LOCK_NAME]);
                Artisan::call('migrate', ['--force' => true]);
            } finally {
                DB::statement('SELECT RELEASE_LOCK(?)', [self::MIGRATION_LOCK_NAME]);
            }
        }

        return $next($request);
    }

    public function isDatabaseConfigured(): bool
    {
        return !empty($this->settings->get('mysql_hostname'))
            && !empty($this->settings->get('mysql_database'));
    }

    public function migrationsShouldRun(): bool
    {
        $table = config('database.migrations');

        if (!Schema::hasTable($table)) {
            return true;
        }

        return !DB::table($table)
            ->where('migration', self::LATEST_MIGRATION)
            ->exists();
    }
}
