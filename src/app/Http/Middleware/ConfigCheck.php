<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
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
