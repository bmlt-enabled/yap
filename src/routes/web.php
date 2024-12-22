<?php

use Illuminate\Support\Facades\Route;

$ext = '(\.php)?$';

//Route::post("/admin/installer", 'App\Http\Controllers\AdminController@installer')->name('installer');
//Route::get("/admin/{page}", 'App\Http\Controllers\AdminController@index')
//    ->middleware("authForAdminPortal");
Route::get("/adminv2{page}", 'App\Http\Controllers\AdminV2Controller@index')
    ->where('page', '.*')
    ->name("adminPortal");
Route::get("/bots/getMeetings", 'App\Http\Controllers\BotController@getMeetings');
Route::get("/bots/getServiceBodyCoverage", 'App\Http\Controllers\BotController@getServiceBodyCoverage');
Route::get("/msr/{latitude}/{longitude}", ['uses' => 'App\Http\Controllers\MeetingResultsController@index'])
    ->where(['latitude' => '.*', 'longitude' => '.*']);
Route::match(array('GET', 'POST'), "/fetch-jft{ext}", 'App\Http\Controllers\ReadingController@jft')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/fetch-spad{ext}", 'App\Http\Controllers\ReadingController@spad')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/ping{ext}", 'App\Http\Controllers\PingController@index')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/", 'App\Http\Controllers\CallFlowController@index')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/info", 'App\Http\Controllers\CallFlowController@info')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/index{ext}", 'App\Http\Controllers\CallFlowController@index')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/input-method{ext}", 'App\Http\Controllers\CallFlowController@inputMethod')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/custom-ext{ext}", 'App\Http\Controllers\CallFlowController@customext')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/input-method-result{ext}", 'App\Http\Controllers\CallFlowController@inputMethodResult')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/zip-input{ext}", 'App\Http\Controllers\CallFlowController@zipinput')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/city-or-county-voice-input{ext}", 'App\Http\Controllers\CallFlowController@cityorcountyinput')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/service-body-ext-response{ext}", 'App\Http\Controllers\CallFlowController@servicebodyextresponse')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/gender-routing-response{ext}", 'App\Http\Controllers\CallFlowController@genderroutingresponse')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/voice-input-result{ext}", 'App\Http\Controllers\CallFlowController@voiceinputresult')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/address-lookup{ext}", 'App\Http\Controllers\CallFlowController@addresslookup')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/playlist{ext}", 'App\Http\Controllers\CallFlowController@playlist')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/fallback{ext}", 'App\Http\Controllers\CallFlowController@fallback')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/custom-ext-dialer{ext}", 'App\Http\Controllers\CallFlowController@customextdialer')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/dialback-dialer{ext}", 'App\Http\Controllers\CallFlowController@dialbackDialer')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/dialback{ext}", 'App\Http\Controllers\CallFlowController@dialback')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/gender-routing{ext}", 'App\Http\Controllers\CallFlowController@genderrouting')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/province-lookup-list-response{ext}", 'App\Http\Controllers\CallFlowController@provincelookuplistresponse')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/status{ext}", 'App\Http\Controllers\CallFlowController@statusCallback')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/voicemail-complete{ext}", 'App\Http\Controllers\VoicemailController@complete')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/voicemail{ext}", 'App\Http\Controllers\VoicemailController@start')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/post-call-action{ext}", 'App\Http\Controllers\CallFlowController@postCallAction')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "upgrade-advisor{ext}", 'App\Http\Controllers\UpgradeAdvisorController@index')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/version", 'App\Http\Controllers\UpgradeAdvisorController@version');
Route::match(array('GET', 'POST'), "/lng-selector{ext}", 'App\Http\Controllers\CallFlowController@languageSelector')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/province-voice-input{ext}", 'App\Http\Controllers\CallFlowController@provinceVoiceInput')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/helpline-answer-response{ext}", 'App\Http\Controllers\CallFlowController@helplineAnswerResponse')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/helpline-outdial-response{ext}", 'App\Http\Controllers\CallFlowController@helplineOutdialResponse')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/sms-gateway{ext}", 'App\Http\Controllers\CallFlowController@smsGateway')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/meeting-search{ext}", 'App\Http\Controllers\CallFlowController@meetingSearch')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/helpline-search{ext}", 'App\Http\Controllers\HelplineController@search')
    ->where('ext', $ext);
Route::match(array('GET', 'POST'), "/helpline-dialer{ext}", 'App\Http\Controllers\HelplineController@dial')
    ->where('ext', $ext);
Route::get("/callWidget", 'App\Http\Controllers\CallWidgetController@index');
