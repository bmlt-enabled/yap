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
Route::get("/auth/logout", 'App\Http\Controllers\AuthController@logout');
Route::get("/auth/invalid", 'App\Http\Controllers\AuthController@invalid');
Route::any('{all}', ['uses' => 'App\Http\Controllers\LegacyController@index'])
    ->where('all', '^(?!api).*$');
