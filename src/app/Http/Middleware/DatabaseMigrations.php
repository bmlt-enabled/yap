<?php

namespace App\Http\Middleware;

use App\Services\DatabaseMigrationService;
use App\Services\SettingsService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DatabaseMigrations
{
    private SettingsService $settings;
    private DatabaseMigrationService $migrationService;

    public function __construct(SettingsService $settings, DatabaseMigrationService $migrationService)
    {
        $this->settings = $settings;
        $this->migrationService = $migrationService;
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
        if (!$this->settings->has('mysql_hostname')) {
            return $next($request);
        }

        $destructive = $this->migrationService->pendingDestructiveMigrationNames();
        if (!empty($destructive)) {
            return response()->view(
                'admin.pending-destructive-migrations',
                ['migrations' => $destructive],
                503
            );
        }

        $this->migrationService->runSafePendingMigrations();

        return $next($request);
    }
}
