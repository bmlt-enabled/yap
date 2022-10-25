<?php

use Illuminate\Support\Facades\Route;

$ext = '(\.php)?$';

Route::get('/v1/events/status', 'App\Http\Controllers\EventStatusController@index');
Route::post('/v1/events/status', 'App\Http\Controllers\EventStatusController@set');
Route::get("/admin/auth/rights", 'App\Http\Controllers\AuthController@rights');
Route::get("/admin/auth/logout", 'App\Http\Controllers\AuthController@logout');
Route::get("/admin/auth/timeout", 'App\Http\Controllers\AuthController@timeout');
Route::get("/admin/auth/invalid", 'App\Http\Controllers\AuthController@invalid');
Route::get("/msr/{latitude}/{longitude}", ['uses' => 'App\Http\Controllers\LegacyController@msr'])
    ->where(['latitude' => '.*', 'longitude' => '.*']);
Route::delete("/admin/cache", 'App\Http\Controllers\AdminController@cacheClear');
Route::get("/fetch-jft{ext}", 'App\Http\Controllers\FetchJFTController@index')
    ->where('ext', $ext);
Route::get("/ping{ext}", 'App\Http\Controllers\PingController@index')
    ->where('ext', $ext);
Route::get("/custom-ext{ext}", 'App\Http\Controllers\CallFlowController@customext')
    ->where('ext', $ext);
Route::get("/zip-input{ext}", 'App\Http\Controllers\CallFlowController@zipinput')
    ->where('ext', $ext);
Route::get("/city-or-county-voice-input{ext}", 'App\Http\Controllers\CallFlowController@cityorcountyinput')
    ->where('ext', $ext);
Route::get("/service-body-ext-response{ext}", 'App\Http\Controllers\CallFlowController@servicebodyextresponse')
    ->where('ext', $ext);
Route::any('{all}', ['uses' => 'App\Http\Controllers\LegacyController@index'])
    ->where('all', '^(?!api).*$');
