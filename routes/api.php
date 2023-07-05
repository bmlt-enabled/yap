<?php

use App\Http\Controllers\Api\V1\Admin\SwaggerController;
use Illuminate\Support\Facades\Route;

// TODO: implement auth later
Route::group([
    'prefix' => 'v1',
    'as' => 'api.',
    'namespace' => 'App\Http\Controllers\Api\V1\Admin',
    //'middleware' => ['auth:api']
], function () {
    Route::get('/openapi.json', [SwaggerController::class, 'openapi'])->name('openapi');
    Route::resource('config', 'ConfigController')->only(['index', 'store']);
    Route::resource('users', 'UserController')->only(['index', 'store', 'destroy', 'update']);
    Route::resource('volunteers/schedule', 'VolunteerScheduleController')->only(['index']);
    Route::resource('volunteers/download', 'VolunteerDownloadController')->only(['index']);
    Route::resource('volunteers/groups', 'VolunteerGroupsController')->only(['index']);
    Route::resource('reports/cdr', 'CdrController')->only(['index']);
    Route::resource('reports/mapmetrics', 'MapMetricController')->only(['index']);
    Route::resource('reports/metrics', 'MetricController')->only(['index']);
    Route::resource('rootServer/servicebodies', 'RootServerServiceBodiesController')->only(['index']);
    Route::resource('events/status', 'EventStatusController')->only(['index', 'store']);
});
