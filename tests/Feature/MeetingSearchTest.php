<?php

use App\Models\MeetingResults;
use App\Models\Timezone;
use App\Services\HttpService;
use App\Services\MeetingResultsService;
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
    $this->from = "+15005550006";
    $this->to = "+15005550007";
    $this->message = "test message";

    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
    $this->twilioService = mock(TwilioService::class)->makePartial();

    $this->latitude = "42.867970";
    $this->longitude = "-76.985573";
});

test('meeting search with a failure on BMLT server exception', function ($method) {
    $settingsService = new SettingsService();
    app()->instance(SettingsService::class, $settingsService);
    $http = mock('App\Services\HttpService')->makePartial();
    $http->shouldReceive("get")->withAnyArgs()->andThrow(Exception::class);
    app()->instance(HttpService::class, $http);

    $response = $this->call($method, '/meeting-search.php', [
        "Latitude" => 0,
        "Longitude" => 0,
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response><Redirect method="GET">fallback.php</Redirect></Response>',
        ], false);
})->with(['GET', 'POST']);

test('meeting search with no more results today', function ($method) {
    $settingsService = new SettingsService();
    app()->instance(SettingsService::class, $settingsService);

    $meetingResults = new MeetingResults();
    $meetingResults->originalListCount = 1;

    $meetingResultsService = mock('\App\Services\MeetingResultsService')->makePartial();
    $meetingResultsService->shouldReceive('getMeetings')
        ->withAnyArgs()->once()->andReturn($meetingResults);

    app()->instance(MeetingResultsService::class, $meetingResultsService);

    $response = $this->call($method, '/meeting-search.php', [
        "Latitude" => 0,
        "Longitude" => 0,
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'there are no other meetings for today..... try again',
            '</Say>',
            '<Redirect method="GET">input-method.php?Digits=2</Redirect>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);

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

test('meeting search with valid latitude and longitude suppressing voice results', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set("suppress_voice_results", true);
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

    $response = $this->call($method, '/meeting-search.php', [
        'Latitude' => $this->latitude,
        'Longitude' => $this->longitude,
        'To' => $this->to,
        'From' => $this->from,
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">5 meetings have been texted to you</Say>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>'
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
        'Longitude' => $this->longitude,
        'To' => $this->to,
        'From' => $this->from,
        'Timestamp' => '2024-02-19 00:00:00'
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 5 results</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">Step up and Be Free</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:00 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">128 Main street, Clifton Springs, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">A New Way of Life</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 6:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">27 West Genesee Street, Clyde, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">Ties That Bind Us Together</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 6:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">99 South St, Auburn, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 4</Say>',
            '<Say voice="alice" language="en-US">Courage to Change</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 10:15 AM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">12 South Street, Auburn, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 5</Say>',
            '<Say voice="alice" language="en-US">Eye of the Hurricane</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">1008 Main St., East Rochester, NY</Say>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>',
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
        'Longitude' => $this->longitude,
        'To' => $this->to,
        'From' => $this->from,
        'Timestamp' => '2024-02-19 00:00:00'
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertDontSee("post-call-action.php")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 3 results</Say>',
            '<Say voice="alice" language="en-US">Meeting search results will also be sent to you by SMS text message.</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">Step up and Be Free</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:00 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">128 Main street, Clifton Springs, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">A New Way of Life</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 6:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">27 West Genesee Street, Clyde, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">Ties That Bind Us Together</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 6:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">99 South St, Auburn, NY</Say>',
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
        'Longitude' => $this->longitude,
        'Timestamp' => '2024-02-19 00:00:00'
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSee("post-call-action.php")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 5 results</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">Step up and Be Free</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:00 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">128 Main street, Clifton Springs, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">A New Way of Life</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 6:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">27 West Genesee Street, Clyde, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">Ties That Bind Us Together</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 6:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">99 South St, Auburn, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 4</Say>',
            '<Say voice="alice" language="en-US">Courage to Change</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 10:15 AM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">12 South Street, Auburn, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 5</Say>',
            '<Say voice="alice" language="en-US">Eye of the Hurricane</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">1008 Main St., East Rochester, NY</Say>',
            '<Pause length="2"/>',
            '<Gather numDigits="1" timeout="10" speechTimeout="auto" input="dtmf" action="post-call-action',
            '<Say voice="alice" language="en-US">press one if you would like these results to be texted to you.</Say>',
            '</Gather>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>',
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
        'Longitude' => $this->longitude,
        'To' => $this->to,
        'From' => $this->from,
        'Timestamp' => '2024-02-19 00:00:00'
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 5 results</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">Step up and Be Free</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:00 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">128 Main street, Clifton Springs, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">A New Way of Life</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 6:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">27 West Genesee Street, Clyde, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">Ties That Bind Us Together</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 6:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">99 South St, Auburn, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 4</Say>',
            '<Say voice="alice" language="en-US">Courage to Change</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 10:15 AM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">12 South Street, Auburn, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 5</Say>',
            '<Say voice="alice" language="en-US">Eye of the Hurricane</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">1008 Main St., East Rochester, NY</Say>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);

test('meeting search with valid latitude and longitude with pronunciation override', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set('result_count_max', 3);
    $settingsService->set('pronunciations', [[
        "source"=>"Auburn",
        "target"=>"Ohhh-it-burns"
    ]]);
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
        'Longitude' => $this->longitude,
        'Timestamp' => '2024-02-19 00:00:00'
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 3 results</Say>',
            '<Say voice="alice" language="en-US">Meeting search results will also be sent to you by SMS text message.</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">Step up and Be Free</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:00 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">128 Main street, Clifton Springs, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">A New Way of Life</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 6:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">27 West Genesee Street, Clyde, NY</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">Ties That Bind Us Together</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 6:30 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">99 South St, Ohhh-it-burns, NY</Say>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
