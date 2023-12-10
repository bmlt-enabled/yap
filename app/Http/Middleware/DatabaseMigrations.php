<?php

namespace App\Http\Middleware;

use App\Services\DatabaseMigrationsService;
use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;

class DatabaseMigrations
{
    private DatabaseMigrationsService $migrations;
    private SettingsService $settings;

    public function __construct(DatabaseMigrationsService $migrations, SettingsService $settings)
    {
        $this->migrations = $migrations;
        $this->settings = $settings;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->settings->has('mysql_hostname')) {
            $this->migrations->catchup();
        }

        return $next($request);
    }
}
