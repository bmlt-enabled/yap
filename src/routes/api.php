<?php

use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\SwaggerController;
use App\Http\Controllers\Api\V1\Admin\VoicemailController;
use App\Http\Controllers\UpgradeAdvisorController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\SettingsController;

Route::group([
    'prefix' => 'v1',
    'as' => 'api.',
    'namespace' => 'App\Http\Controllers\Api\V1\Admin',
], function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::get('version', [UpgradeAdvisorController::class, 'version']);
    Route::get('/openapi.json', [SwaggerController::class, 'openapi'])->name('openapi');

    Route::group(['middleware' => ['auth:sanctum']], function () {
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
            Route::get('settings/localizations', 'getLocalizations');
            Route::get('settings/timezones', 'getTimezones');
            Route::get('settings/serviceBody/{serviceBodyId}', 'getServiceBodyConfiguration');
            Route::post('settings/serviceBody/{serviceBodyId}', 'saveServiceBodyConfiguration');
        });
    });
});

Route::post('/resetDatabase', function () {
    $env = config('app.env'); // Get the current environment
    if ($env === 'production') {
        return response()->json([
            'status' => 'error',
            'message' => 'Cannot reset database in production environment.'
        ], 403);
    }
    Artisan::call('migrate:fresh --seed');
    return response()->json([
        'status' => 'database reset',
        'migrationOutput' => Artisan::output(),
    ]);
});
