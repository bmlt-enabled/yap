<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//Route::get("/storage/{extra}", function ($extra) {
//    return redirect("/public/storage/$extra");
//})->where("extra", ".*");
Route::get("/auth/logout", 'App\Http\Controllers\AuthController@logout');
Route::get("/auth/invalid", 'App\Http\Controllers\AuthController@invalid');
Route::get("/msr/{latitude}/{longitude}", ['uses' => 'App\Http\Controllers\LegacyController@msr'])
    ->where(['latitude' => '.*', 'longitude' => '.*']);
Route::any('{all}', ['uses' => 'App\Http\Controllers\LegacyController@index'])
    ->where('all', '^(?!api).*$');
