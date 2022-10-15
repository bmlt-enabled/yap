<?php

use Illuminate\Support\Facades\Route;

Route::get("/timezone", 'App\Http\Controllers\StubsController@timezone');
Route::get("/geocode", 'App\Http\Controllers\StubsController@geocode');
