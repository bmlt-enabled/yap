<?php

use Illuminate\Support\Facades\Route;

$ext = '(\.php)?$';

Route::get('/v1/session/delete', 'App\Http\Controllers\SessionController@delete');
Route::get("/admin/auth/rights", 'App\Http\Controllers\AuthController@rights');
Route::get("/admin/auth/logout", 'App\Http\Controllers\AuthController@logout');
Route::get("/admin/auth/timeout", 'App\Http\Controllers\AuthController@timeout');
Route::get("/admin/auth/invalid", 'App\Http\Controllers\AuthController@invalid');
Route::get("/adminv2{page}", 'App\Http\Controllers\AdminController@index')
    ->where('page', '.*');
Route::get("/msr/{latitude}/{longitude}", ['uses' => 'App\Http\Controllers\MeetingResultsController@index'])
    ->where(['latitude' => '.*', 'longitude' => '.*']);
Route::delete("/admin/cache", 'App\Http\Controllers\AdminController@cacheClear');
Route::get("/fetch-jft{ext}", 'App\Http\Controllers\FetchJFTController@index')
    ->where('ext', $ext);
Route::get("/fetch-spad{ext}", 'App\Http\Controllers\FetchJFTController@spad')
    ->where('ext', $ext);
Route::get("/ping{ext}", 'App\Http\Controllers\PingController@index')
    ->where('ext', $ext);
Route::get("/", 'App\Http\Controllers\CallFlowController@index')
    ->where('ext', $ext);
Route::get("/index{ext}", 'App\Http\Controllers\CallFlowController@index')
    ->where('ext', $ext);
Route::get("/input-method{ext}", 'App\Http\Controllers\CallFlowController@inputMethod')
    ->where('ext', $ext);
Route::get("/custom-ext{ext}", 'App\Http\Controllers\CallFlowController@customext')
    ->where('ext', $ext);
Route::get("/input-method-result{ext}", 'App\Http\Controllers\CallFlowController@inputMethodResult')
    ->where('ext', $ext);
Route::get("/zip-input{ext}", 'App\Http\Controllers\CallFlowController@zipinput')
    ->where('ext', $ext);
Route::get("/city-or-county-voice-input{ext}", 'App\Http\Controllers\CallFlowController@cityorcountyinput')
    ->where('ext', $ext);
Route::get("/service-body-ext-response{ext}", 'App\Http\Controllers\CallFlowController@servicebodyextresponse')
    ->where('ext', $ext);
Route::get("/gender-routing-response{ext}", 'App\Http\Controllers\CallFlowController@genderroutingresponse')
    ->where('ext', $ext);
Route::get("/voice-input-result{ext}", 'App\Http\Controllers\CallFlowController@voiceinputresult')
    ->where('ext', $ext);
Route::get("/address-lookup{ext}", 'App\Http\Controllers\CallFlowController@addresslookup')
    ->where('ext', $ext);
Route::get("/playlist{ext}", 'App\Http\Controllers\CallFlowController@playlist')
    ->where('ext', $ext);
Route::get("/fallback{ext}", 'App\Http\Controllers\CallFlowController@fallback')
    ->where('ext', $ext);
Route::get("/custom-ext-dialer{ext}", 'App\Http\Controllers\CallFlowController@customextdialer')
    ->where('ext', $ext);
Route::get("/dialback-dialer{ext}", 'App\Http\Controllers\CallFlowController@dialbackDialer')
    ->where('ext', $ext);
Route::get("/dialback{ext}", 'App\Http\Controllers\CallFlowController@dialback')
    ->where('ext', $ext);
Route::get("/gender-routing{ext}", 'App\Http\Controllers\CallFlowController@genderrouting')
    ->where('ext', $ext);
Route::get("/province-lookup-list-response{ext}", 'App\Http\Controllers\CallFlowController@provincelookuplistresponse')
    ->where('ext', $ext);
Route::get("/status{ext}", 'App\Http\Controllers\CallFlowController@statusCallback')
    ->where('ext', $ext);
Route::get("/voicemail-complete{ext}", 'App\Http\Controllers\VoicemailController@complete')
    ->where('ext', $ext);
Route::get("/voicemail{ext}", 'App\Http\Controllers\VoicemailController@start')
    ->where('ext', $ext);
Route::get("/post-call-action{ext}", 'App\Http\Controllers\CallFlowController@postCallAction')
    ->where('ext', $ext);
Route::get("/upgrade-advisor{ext}", 'App\Http\Controllers\UpgradeAdvisorController@index')
    ->where('ext', $ext);
Route::get("/lng-selector{ext}", 'App\Http\Controllers\CallFlowController@languageSelector')
    ->where('ext', $ext);
Route::get("/province-voice-input{ext}", 'App\Http\Controllers\CallFlowController@provinceVoiceInput')
    ->where('ext', $ext);
Route::get("/helpline-sms{ext}", 'App\Http\Controllers\CallFlowController@helplineSms')
    ->where('ext', $ext);
Route::get("/helpline-answer-response{ext}", 'App\Http\Controllers\CallFlowController@helplineAnswerResponse')
    ->where('ext', $ext);
Route::get("/helpline-outdial-response{ext}", 'App\Http\Controllers\CallFlowController@helplineOutdialResponse')
    ->where('ext', $ext);
Route::get("/sms-gateway{ext}", 'App\Http\Controllers\CallFlowController@smsGateway')
    ->where('ext', $ext);
Route::get("/meeting-search{ext}", 'App\Http\Controllers\CallFlowController@meetingSearch')
    ->where('ext', $ext);
Route::get("/helpline-search{ext}", 'App\Http\Controllers\HelplineController@search')
    ->where('ext', $ext);
Route::get("/helpline-dialer{ext}", 'App\Http\Controllers\HelplineController@dial')
    ->where('ext', $ext);
//Route::any('{all}', ['uses' => 'App\Http\Controllers\LegacyController@index'])
//    ->where('all', '^(?!api).*$');
