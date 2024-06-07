<?php

use App\Models\Alert;
use App\Services\GeocodingService;
use App\Services\SettingsService;
use App\Services\TimeZoneService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
    $this->twilioService = mock(TwilioService::class)->makePartial();
});

test('version test', function ($method) {

    $settings = new SettingsService();
    app()->instance(SettingsService::class, $settings);
    $response = $this->call($method, '/version');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            'version' => $settings->version(),
        ]);
})->with(['GET', 'POST']);

test('version test as jsonp', function ($method) {
    $settings = new SettingsService();
    app()->instance(SettingsService::class, $settings);
    $response = $this->call($method, '/version?callback=bro');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/javascript")
        ->assertSeeText(sprintf("bro({\"version\":\"%s\"})", $settings->version()), false);
})->with(['GET', 'POST']);

test('version test check cors headers', function ($method) {
    $settings = new SettingsService();
    app()->instance(SettingsService::class, $settings);
    $response = $this->call($method, '/version');
    $response
        ->assertStatus(200)
        ->assertHeader("Access-Control-Allow-Origin", "*")
        ->assertSeeText(sprintf("{\"version\":\"%s\"}", $settings->version()), false);
})->with(['GET', 'POST']);

test('test with misconfigured phone number', function ($method) {
    $misconfiguredNumber = "+18889822614";
    $settingsService = new SettingsService();
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);
    $geocodingService = mock(GeocodingService::class)->makePartial();
    app()->instance(SettingsService::class, instance: $settingsService);

    $geocodingService
        ->shouldReceive("ping")
        ->withArgs(["91409"])
        ->andReturn((object)['status' => 'OK'])
        ->once();
    app()->instance(GeocodingService::class, $geocodingService);

    $timezoneService = mock(TimeZoneService::class)->makePartial();
    $timezoneService
        ->shouldReceive("getTimeZoneForCoordinates")
        ->withAnyArgs()
        ->andReturn((object)['status' => 'OK'])
        ->once();
    app()->instance(TimeZoneService::class, $timezoneService);

    Alert::createMisconfiguredPhoneNumberAlert($misconfiguredNumber);

    $this->twilioService->client()->shouldReceive('getAccountSid')->andReturn("123");
    $incomingPhoneNumberContext = mock('\Twilio\Rest\Api\V2010\Account\InstanceContext');
    $incomingPhoneNumberInstance= mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberInstance');
    $incomingPhoneNumberInstance->voiceUrl = "http://localhost:3100/yap/index.php";
    $incomingPhoneNumberContext->shouldReceive('read')->withNoArgs()
        ->andReturn([$incomingPhoneNumberInstance])->once();

    // mocking TwilioRestClient->incomingPhoneNumbers->read();
    $this->twilioService->client()->incomingPhoneNumbers = $incomingPhoneNumberContext;

    app()->instance(TwilioService::class, $this->twilioService);

    $response = $this->call($method, '/upgrade-advisor.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson(
            [
                "status"=>true,
                "message"=>"Ready To Yap!",
                "warnings"=>sprintf("%s is/are phone numbers that are missing Twilio Call Status Changes Callback status.php webhook. This will not allow call reporting to work correctly.  For more information review the documentation page https://github.com/bmlt-enabled/yap/wiki/Call-Detail-Records.", $misconfiguredNumber),
                "version"=>$settingsService->version(),
                "build"=>"local"
            ]
        );
})->with(['GET', 'POST']);

test('test with smtp settings missing', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set("smtp_host", "bro");
    $settingsService->set("smtp_username", "bro");
    $settingsService->set("smtp_password", "bro");
    $settingsService->set("smtp_secure", false);
    $settingsService->set("smtp_from_address", "bro@bro.com");
    $settingsService->set("smtp_from_name", "name");
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);
    $geocodingService = mock(GeocodingService::class)->makePartial();
    app()->instance(SettingsService::class, instance: $settingsService);

    $geocodingService
        ->shouldReceive("ping")
        ->withArgs(["91409"])
        ->andReturn((object)['status' => 'OK'])
        ->once();
    app()->instance(GeocodingService::class, $geocodingService);

    $timezoneService = mock(TimeZoneService::class)->makePartial();
    $timezoneService
        ->shouldReceive("getTimeZoneForCoordinates")
        ->withAnyArgs()
        ->andReturn((object)['status' => 'OK'])
        ->once();
    app()->instance(TimeZoneService::class, $timezoneService);

    $this->twilioService->client()->shouldReceive('getAccountSid')->andReturn("123");
    $incomingPhoneNumberContext = mock('\Twilio\Rest\Api\V2010\Account\InstanceContext');
    $incomingPhoneNumberInstance= mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberInstance');
    $incomingPhoneNumberInstance->voiceUrl = "http://localhost:3100/yap/index.php";
    $incomingPhoneNumberContext->shouldReceive('read')->withNoArgs()
        ->andReturn([$incomingPhoneNumberInstance])->once();

    // mocking TwilioRestClient->incomingPhoneNumbers->read();
    $this->twilioService->client()->incomingPhoneNumbers = $incomingPhoneNumberContext;

    app()->instance(TwilioService::class, $this->twilioService);

    $response = $this->call($method, '/upgrade-advisor.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson(
            [
                "status"=>true,
                "message"=>"Ready To Yap!",
                "warnings"=>sprintf(""),
                "version"=>$settingsService->version(),
                "build"=>"local"
            ]
        );
})->with(['GET', 'POST']);

//test('bad google maps api key', function () {
//    $settings = new SettingsService();
//    $GLOBALS['google_maps_endpoint'] = 'https://maps.googleapis.com/maps/api/geocode/json?key=bad_key';
//    app()->instance(SettingsService::class, $settings);
//    $response = $this->get('/upgrade-advisor.php');
//    $response
//        ->assertStatus(200)
//        ->assertHeader("Content-Type", "application/json")
//        ->assertJson([
//            'status' => false,
//            'message' => 'Your Google Maps API key came back with the following error. The provided API key is invalid.  Please make sure you have the Google Maps Geocoding API enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/',
//            'warnings' => '',
//            'version' => $settings->version(),
//            'build' => 'local'
//        ]);
//});

//test('bad timezone api key', function () {
//    $GLOBALS['google_maps_endpoint'] = 'http://localhost:3100/yap/stub/geocode?key=stub';
//    $response = $this->get('/upgrade-advisor.php');
//    $response
//        ->assertStatus(200)
//        ->assertHeader("Content-Type", "application/json")
//        ->assertJson([
//            'status' => false,
//            'message' => 'Your Google Maps API key came back with the following error. The provided API key is invalid. Please make sure you have the Google Time Zone API enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/',
//            'warnings' => '',
//            'version' => $GLOBALS['version'],
//            'build' => 'local'
//        ]);
//});
