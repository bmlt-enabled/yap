<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CallBlocklist;
use App\Http\Middleware\CallSession;
use App\Http\Middleware\ConfigCheck;
use App\Http\Middleware\DatabaseMigrations;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\JsonpMiddleware;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\SessionKey;
use App\Http\Middleware\SmsBlackhole;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\ValidateTwilioSignature;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel
{
    /**
     * Run API throttling before authentication so unauthenticated bursts on
     * auth:sanctum routes are rate-limited instead of returning 401 forever.
     *
     * @var array<int, class-string|string>
     */
    protected $middlewarePriority = [
        \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
        \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
        \Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];

    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
//        \App\Http\Middleware\TrustHosts::class,
        TrustProxies::class,
        HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        SessionKey::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        ConfigCheck::class,
        DatabaseMigrations::class,
        CallSession::class,
        JsonpMiddleware::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            // StartSession also runs in the global stack; the middleware is
            // idempotent and does not double-start or reset the session.
            StartSession::class,
            // Off in 4.5.x; Sanctum bearer tokens are the primary API auth path.
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            // ShareErrorsFromSession runs in the global stack instead.
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            CallBlocklist::class,
            SmsBlackhole::class,
        ],

        'api' => [
            // SPA login uses session()->regenerate(); global StartSession covers API
            // routes — EnsureFrontendRequestsAreStateful is not required today.
            'throttle:api',
            SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        // authForAdminPortal, auth.basic, and guest were removed — no routes reference them.
        'auth' => Authenticate::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'password.confirm' => RequirePassword::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        'twilio.signature' => ValidateTwilioSignature::class,
    ];
}
