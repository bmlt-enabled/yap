<?php

use Illuminate\Support\Facades\Route;

// Control/inspection endpoints for fake Twilio mode (see FakeTwilioController).
// Included from web.php inside the twilio.signature group, and only when
// FakeTwilioStore::enabled().
Route::get("/fake-twilio/requests", 'App\Http\Controllers\FakeTwilioController@requests');
Route::post("/fake-twilio/reset", 'App\Http\Controllers\FakeTwilioController@reset');
Route::post("/fake-twilio/calls", 'App\Http\Controllers\FakeTwilioController@registerCall');
Route::get("/fake-twilio/calls/{sid}", 'App\Http\Controllers\FakeTwilioController@call');
Route::post("/fake-twilio/conferences/join", 'App\Http\Controllers\FakeTwilioController@joinConference');
Route::post("/fake-twilio/conferences/leave", 'App\Http\Controllers\FakeTwilioController@leaveConference');
