<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use App\Services\SettingsService;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60);
        });

        // WebRTC token endpoint - configurable limit (default: 5/min)
        RateLimiter::for('webrtc-token', function (Request $request) {
            $limit = app(SettingsService::class)->get('webrtc_token_rate_limit') ?: 5;
            return Limit::perMinute($limit)->by($request->ip());
        });

        // WebRTC call endpoint - configurable limit (default: 3/min)
        RateLimiter::for('webrtc-call', function (Request $request) {
            $limit = app(SettingsService::class)->get('webrtc_call_rate_limit') ?: 3;
            return Limit::perMinute($limit)->by($request->ip());
        });

        // WebChat endpoint - configurable limit (default: 10/min)
        RateLimiter::for('webchat', function (Request $request) {
            $limit = app(SettingsService::class)->get('webchat_rate_limit') ?: 10;
            return Limit::perMinute($limit)->by($request->ip());
        });
    }
}
