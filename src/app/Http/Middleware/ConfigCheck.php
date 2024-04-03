<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;

class ConfigCheck
{
    private SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
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
//        if (!file_exists('./config.php') && !str_ends_with($request->url(), 'installer')) {
//            return response()->view(
//                'admin/installer',
//                ['minimalRequiredSettings'=>$this->settingsService->minimalRequiredSettings()]
//            );
//        }

        return $next($request);
    }
}
