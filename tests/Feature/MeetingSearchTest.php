<?php

use App\Models\Timezone;
use App\Services\SettingsService;
use App\Services\TimeZoneService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;
use Twilio\Rest\Client;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
    $this->from = "+15005550006";
    $this->to = "+15005550007";
    $this->message = "test message";
    $_REQUEST['To'] = $this->to;
    $_REQUEST['From'] = $this->from;

    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
    $this->twilioService = mock(TwilioService::class)->makePartial();

    $this->latitude = '35.7796';
    $this->longitude = '-78.6382';
});

test('meeting search with odd coordinates on meeting lookup', function ($method) {
    $response = $this->call($method, '/meeting-search.php', [
        "Latitude" => 0,
        "Longitude" => 0,
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertDontSee("post-call-action.php")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Say voice="alice" language="en-US">no results found... you might have an invalid entry... try again</Say>',
            '<Redirect method="GET">input-method.php?Digits=2</Redirect>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);

test('meeting search with valid latitude and longitude', function ($method) {
    $settingsService = new SettingsService();
    app()->instance(SettingsService::class, $settingsService);
    app()->instance(TwilioService::class, $this->twilioService);
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);

    $timezone = new Timezone('OK', 0, -18000, 'America/New_York', 'Eastern Standard Time');
    $timezoneService = mock(TimeZoneService::class)->makePartial();
    $timezoneService->shouldReceive('getTimeZoneForCoordinates')
        ->withArgs([$this->latitude, $this->longitude])
        ->once()
        ->andReturn($timezone);
    app()->instance(TimeZoneService::class, $timezoneService);

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]))->times(5);
    $this->twilioClient->messages = $messageListMock;

//    $phoneNumbersLookup = mock('\Twilio\Rest\Lookups\V1\PhoneNumberList');
//    $phoneNumbersLookup->shouldReceive('phoneNumbers')
//        ->withArgs([$_REQUEST['From']]);
//    $this->twilioClient->lookups->v1->phoneNumbers = $phoneNumbersLookup;
//    $phoneNumbersLookup->shouldReceive('fetch')
//        ->withArgs(array("type" => "carrier"))
//        ->once()
//        ->andReturn(["carrier" => ["type" => "mobile"])

    $response = $this->call($method, '/meeting-search.php', [
        'Latitude' => $this->latitude,
        'Longitude' => $this->longitude
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertDontSee("post-call-action.php")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 5 results',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 4</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 5</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('meeting search with valid latitude and longitude different results count max', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set('sms_combine', false);
    $settingsService->set('sms_ask', false);
    $settingsService->set('result_count_max', 3);
    app()->instance(SettingsService::class, $settingsService);
    app()->instance(TwilioService::class, $this->twilioService);
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]))->times(3);
    $this->twilioService->client()->messages = $messageListMock;

    $timezone = new Timezone('OK', 0, -18000, 'America/New_York', 'Eastern Standard Time');
    $timezoneService = mock(TimeZoneService::class)->makePartial();
    $timezoneService->shouldReceive('getTimeZoneForCoordinates')
        ->withArgs([$this->latitude, $this->longitude])
        ->once()
        ->andReturn($timezone);
    app()->instance(TimeZoneService::class, $timezoneService);

    $response = $this->call($method, '/meeting-search.php', [
        'Latitude' => $this->latitude,
        'Longitude' => $this->longitude
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertDontSee("post-call-action.php")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 3 results',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('meeting search with valid latitude and longitude with sms ask', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set("sms_ask", true);
    app()->instance(SettingsService::class, $settingsService);

    $timezone = new Timezone('OK', 0, -18000, 'America/New_York', 'Eastern Standard Time');
    $timezoneService = mock(TimeZoneService::class)->makePartial();
    $timezoneService->shouldReceive('getTimeZoneForCoordinates')
        ->withArgs([$this->latitude, $this->longitude])
        ->once()
        ->andReturn($timezone);
    app()->instance(TimeZoneService::class, $timezoneService);

    $response = $this->call($method, '/meeting-search.php', [
        'Latitude' => $this->latitude,
        'Longitude' => $this->longitude
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSee("post-call-action.php")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 5 results',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 4</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 5</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Pause length="2"/>',
            '<Gather numDigits="1" timeout="10" speechTimeout="auto" input="dtmf" action="post-call-action.php',
            '<Say voice="alice" language="en-US">press one if you would like these results to be texted to you.</Say></Gather>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('meeting search with valid latitude and longitude with sms combine', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set('sms_combine', true);
    $settingsService->set('sms_ask', false);
    app()->instance(SettingsService::class, $settingsService);
    app()->instance(TwilioService::class, $this->twilioService);
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);

    $timezone = new Timezone('OK', 0, -18000, 'America/New_York', 'Eastern Standard Time');
    $timezoneService = mock(TimeZoneService::class)->makePartial();
    $timezoneService->shouldReceive('getTimeZoneForCoordinates')
        ->withArgs([$this->latitude, $this->longitude])
        ->once()
        ->andReturn($timezone);
    app()->instance(TimeZoneService::class, $timezoneService);

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]))->times(1);
    $this->twilioClient->messages = $messageListMock;

    $response = $this->call($method, '/meeting-search.php', [
        'Latitude' => $this->latitude,
        'Longitude' => $this->longitude
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 5 results',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 4</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 5</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
