<?php

use App\Services\HttpService;
use App\Services\MeetingResultsService;
use App\Services\SettingsService;
use App\Services\TimeZoneService;
use App\Services\TwilioService;
use App\Structures\MeetingResults;
use App\Structures\Timezone;
use Tests\FakeTwilioHttpClient;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertDontSee("post-call-action.php")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertDontSee("post-call-action.php")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 3 results</Say>',
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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSee("post-call-action.php")
        ->assertSeeInOrderExact([
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
            '<Gather numDigits="1" timeout="10" speechTimeout="auto" input="dtmf" action="post-call-action.php?Payload=%5B%22Step+up+and+Be+Free+Monday+7%3A00+PM%2C+128+Main+street%2C+Clifton+Springs%2C+NY%22%2C%22A+New+Way+of+Life+Monday+6%3A30+PM%2C+27+West+Genesee+Street%2C+Clyde%2C+NY%22%2C%22Ties+That+Bind+Us+Together+Monday+6%3A30+PM%2C+99+South+St%2C+Auburn%2C+NY%22%2C%22Courage+to+Change+Monday+10%3A15+AM%2C+12+South+Street%2C+Auburn%2C+NY%22%2C%22Eye+of+the+Hurricane+Monday+7%3A30+PM%2C+1008+Main+St.%2C+East+Rochester%2C+NY%22%5D" method="GET">',
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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
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
    ], [
        "source"=>"Clifton Springs",
        "target"=>"Cliftonnnn Springs"
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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 3 results</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">Step up and Be Free</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:00 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">128 Main street, Cliftonnnn Springs, NY</Say>',
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

test('meeting search with valid latitude and longitude with sms summary page', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set('sms_summary_page', true);
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
        ->withArgs([$this->from, ["from" => $this->to, "body" => sprintf("Meeting Results, click here: https://localhost/msr/%s/%s", $this->latitude, $this->longitude)]])
        ->once();
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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
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

test('meeting search with map links', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set('include_map_link', true);
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
        ->withArgs([$this->from, ["from" => $this->to, "body" => "Step up and Be Free Monday 7:00 PM, 128 Main street, Clifton Springs, NY https://maps.google.com/maps?q=42.9613734,-77.1205486&hl="]])
        ->once();
    $messageListMock->shouldReceive('create')
        ->withArgs([$this->from, ["from" => $this->to, "body" => "A New Way of Life Monday 6:30 PM, 27 West Genesee Street, Clyde, NY https://maps.google.com/maps?q=43.0851169,-76.872356&hl="]])
        ->once();
    $messageListMock->shouldReceive('create')
        ->withArgs([$this->from, ["from" => $this->to, "body" => "Ties That Bind Us Together Monday 6:30 PM, 99 South St, Auburn, NY https://maps.google.com/maps?q=42.923231,-76.566373&hl="]])
        ->once();
    $messageListMock->shouldReceive('create')
        ->withArgs([$this->from, ["from" => $this->to, "body" => "Courage to Change Monday 10:15 AM, 12 South Street, Auburn, NY https://maps.google.com/maps?q=42.9313221,-76.5656656&hl="]])
        ->once();
    $messageListMock->shouldReceive('create')
        ->withArgs([$this->from, ["from" => $this->to, "body" => "Eye of the Hurricane Monday 7:30 PM, 1008 Main St., East Rochester, NY https://maps.google.com/maps?q=43.1066,-77.487084&hl="]])
        ->once();

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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
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

test('meeting search with location text', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set('include_location_text', true);
    $settingsService->set('result_count_max', 1);
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
        ->withArgs([$this->from, ["from" => $this->to, "body" => "Step up and Be Free Monday 7:00 PM, Clifton springs Hospital, 128 Main street, Clifton Springs, NY"]])
        ->once();

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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 1 results</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">Step up and Be Free</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:00 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">Clifton springs Hospital</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">128 Main street, Clifton Springs, NY</Say>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);

test('meeting search with distance details in miles', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set('include_distance_details', 'mi');
    $settingsService->set('result_count_max', 1);
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
        ->withArgs([$this->from, ["from" => $this->to, "body" => "Step up and Be Free Monday 7:00 PM, 128 Main street, Clifton Springs, NY (9 mi)"]])
        ->once();

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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 1 results</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">Step up and Be Free</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:00 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">128 Main street, Clifton Springs, NY</Say>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);

test('meeting search with distance details in km', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set('include_distance_details', 'km');
    $settingsService->set('result_count_max', 1);
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
        ->withArgs([$this->from, ["from" => $this->to, "body" => "Step up and Be Free Monday 7:00 PM, 128 Main street, Clifton Springs, NY (15 km)"]])
        ->once();

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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 1 results</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">Step up and Be Free</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:00 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">128 Main street, Clifton Springs, NY</Say>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);

test('meeting search with say links', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set('say_links', true);
    $settingsService->set('include_format_details', ['TC', 'VM', 'HY']);
    $settingsService->set('result_count_max', 1);
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
        ->withArgs([$this->from, ["from" => $this->to, "body" => "Step up and Be Free Monday 7:00 PM, 128 Main street, Clifton Springs, NY"]])
        ->once();

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
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 1 results</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">Step up and Be Free</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">starts at Monday 7:00 PM</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">128 Main street, Clifton Springs, NY</Say>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);
