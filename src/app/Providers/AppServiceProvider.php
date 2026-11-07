<?php

namespace App\Providers;

use App\Auth\CustomUserProvider;
use App\Listeners\RecordWebhookFailure;
use App\Services\AuthenticationService;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Auth::provider('custom', function ($app, array $config) {
            return new CustomUserProvider($app->make(AuthenticationService::class));
        });

        // Surface Twilio webhook 5xx on System Health (bmlt-enabled/yap#1686).
        Event::listen(RequestHandled::class, RecordWebhookFailure::class);
    }
}
