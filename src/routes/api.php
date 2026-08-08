<?php

use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\SwaggerController;
use App\Http\Controllers\Api\V1\Admin\VoicemailController;
use App\Http\Controllers\Api\V1\WebRtcController;
use App\Http\Controllers\Api\V1\WebChatController;
use App\Http\Controllers\UpgradeAdvisorController;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\SettingsController;

Route::group([
    'prefix' => 'v1',
    'as' => 'api.',
    'namespace' => 'App\Http\Controllers\Api\V1\Admin',
], function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:login')  // 5/min production; relaxed when E2E_TESTING=1
        ->name('login');
    Route::get('version', function (\App\Services\SettingsService $settings) {
        return response()->json(['version' => $settings->version()]);
    });
    Route::get('settings/localizations', [SettingsController::class, 'getLocalizations']);
    Route::get('upgrade', [UpgradeAdvisorController::class, 'index']);
    Route::get('/openapi.json', [SwaggerController::class, 'openapi'])->name('openapi');

    // WebRTC Widget endpoints (public, rate-limited)
    // EXPERIMENTAL, default off: registered only when webrtc_enabled is true, so
    // a disabled feature is a 404 rather than a reachable endpoint that refuses.
    // Token endpoint uses configurable rate limit (default: 5/min)
    if (SettingsService::featureEnabledAtBoot('webrtc_enabled')) {
        Route::group(['prefix' => 'webrtc'], function () {
            Route::get('token', [WebRtcController::class, 'token'])
                ->middleware('throttle:webrtc-token')
                ->name('webrtc.token');
            Route::get('config', [WebRtcController::class, 'config'])
                ->middleware('throttle:60,1')
                ->name('webrtc.config');
        });
    }

    // WebChat Widget endpoints (public, rate-limited)
    // EXPERIMENTAL, default off: see the note on the webrtc group above.
    if (SettingsService::featureEnabledAtBoot('webchat_enabled')) {
        Route::group(['prefix' => 'webchat'], function () {
            Route::get('config', [WebChatController::class, 'config'])
                ->middleware('throttle:60,1')
                ->name('webchat.config');
            Route::get('meetings', [WebChatController::class, 'searchMeetings'])
                ->middleware('throttle:30,1')
                ->name('webchat.meetings');
            Route::get('session', [WebChatController::class, 'getSession'])
                ->middleware('throttle:60,1')
                ->name('webchat.session.get');
            Route::post('session', [WebChatController::class, 'createSession'])
                ->middleware('throttle:webchat')
                ->name('webchat.session.create');
            Route::post('session/{sessionId}/message', [WebChatController::class, 'sendMessage'])
                ->middleware('throttle:webchat')
                ->name('webchat.message.send');
            Route::get('session/{sessionId}/messages', [WebChatController::class, 'getMessages'])
                ->middleware('throttle:120,1')
                ->name('webchat.messages.get');
            Route::post('session/{sessionId}/close', [WebChatController::class, 'closeSession'])
                ->middleware('throttle:60,1')
                ->name('webchat.session.close');
        });
    }

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::resource('user', 'AuthController')->only(['index']);
        Route::resource('volunteers', 'ConfigureVolunteersController')->only(['index', 'store']);
        Route::resource('callHandling', 'ServiceBodyCallHandlingController')->only(['index', 'store']);
        Route::resource('callHandling/routingEnabled', 'VolunteerRoutingEnabledController')->only(['index']);
        Route::resource('users', 'UserController')->only(['index', 'show', 'store', 'destroy', 'update']);
        Route::resource('groups', 'GroupController')->only(['index', 'store', 'destroy', 'update']);
        Route::resource('groups/volunteers', 'GroupVolunteerController')->only(['index', 'store']);
        Route::resource('volunteers/schedule', 'VolunteerScheduleController')->only(['index']);
        Route::resource('volunteers/download', 'VolunteerDownloadController')->only(['index']);
        Route::resource('reports/cdr', 'CdrController')->only(['index']);
        Route::resource('reports/mapmetrics', 'MapMetricController')->only(['index']);
        Route::resource('reports/metrics', 'MetricController')->only(['index']);
        Route::resource('rootServer/serviceBodies', 'RootServerServiceBodiesController')->only(['index']);
        Route::resource('rootServer/serviceBodies/user', 'RootServerServiceBodiesForUserController')->only(['index']);
        Route::resource('events/status', 'EventStatusController')->only(['index', 'store']);
        Route::resource('session', 'SessionController')->only(['store']);
        Route::resource('cache', 'CacheController')->only(['store']);
        Route::resource('voicemail', 'VoicemailController')->only(['index', 'destroy']);
        Route::controller(SettingsController::class)->group(function () {
            Route::get('settings', 'index');
            Route::get('settings/allowlist', 'allowlist');
            Route::get('settings/timezones', 'getTimezones');
            Route::get('settings/serviceBody/{serviceBodyId}', 'getServiceBodyConfiguration');
            Route::post('settings/serviceBody/{serviceBodyId}', 'saveServiceBodyConfiguration');
        });
    });
});
