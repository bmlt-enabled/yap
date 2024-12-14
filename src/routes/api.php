<?php

use App\Http\Controllers\Api\V1\Admin\SwaggerController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1',
    'as' => 'api.',
    'namespace' => 'App\Http\Controllers\Api\V1\Admin',
], function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::group(['middleware' => ['auth:sanctum']], function() {
        Route::get('/openapi.json', [SwaggerController::class, 'openapi'])->name('openapi');
        Route::resource('config', 'ConfigController')->only(['index', 'store']);
        Route::resource('volunteers', 'ConfigureVolunteersController')->only(['index', 'store']);
        Route::resource('callHandling', 'ServiceBodyCallHandlingController')->only(['index', 'store']);
        Route::resource('users', 'UserController')->only(['index', 'show', 'store', 'destroy', 'update']);
        Route::resource('groups', 'GroupController')->only(['index', 'store', 'destroy', 'update']);
        Route::resource('groups/volunteers', 'GroupVolunteerController')->only(['index', 'store']);
        Route::resource('volunteers/schedule', 'VolunteerScheduleController')->only(['index']);
        Route::resource('volunteers/download', 'VolunteerDownloadController')->only(['index']);
        Route::resource('reports/cdr', 'CdrController')->only(['index']);
        Route::resource('reports/mapmetrics', 'MapMetricController')->only(['index']);
        Route::resource('reports/metrics', 'MetricController')->only(['index']);
        Route::resource('rootServer/servicebodies', 'RootServerServiceBodiesController')->only(['index']);
        Route::resource('events/status', 'EventStatusController')->only(['index', 'store']);
        Route::resource('session', 'SessionController')->only(['store']);
        Route::resource("cache", 'CacheController')->only(['store']);
        Route::resource('settings', 'SettingsController')->only(['index']);
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
