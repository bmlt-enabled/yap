<?php

use App\Services\SettingsService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
    $this->to = "+15005550006";
    $this->from = "+12125551212";

    $_SESSION["initial_webhook"] = "https://example.org/index.php";

    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
    $this->twilioService = mock(TwilioService::class)->makePartial();
});

test('standard call ending', function ($method) {
    $response = $this->call($method, '/post-call-action.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'thank you for calling, goodbye</Say></Response>'], false);
})->with(['GET', 'POST']);

test('start over', function ($method) {
    $response = $this->call($method, '/post-call-action.php', ["Digits"=>"2"]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response><Redirect method="GET">https://example.org/index.php</Redirect></Response>'], false);
})->with(['GET', 'POST']);

test('send meeting results SMS', function ($method) {
    $settingsService = new SettingsService();
    app()->instance(SettingsService::class, $settingsService);
    app()->instance(TwilioService::class, $this->twilioService);
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]))->times(2);
    $this->twilioService->client()->messages = $messageListMock;

    $response = $this->call(
        $method,
        '/post-call-action.php',
        ["Digits"=>"1","To" => "+", "From" => "+12125551212",
            "Payload" => "[\"test message 1\", \"test message 2\"]"]
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'thank you for calling, goodbye</Say></Response>'], false);
})->with(['GET', 'POST']);

test('send meeting results SMS with combine', function ($method) {
    $_SESSION["override_sms_combine"] = true;

    $settingsService = new SettingsService();
    app()->instance(SettingsService::class, $settingsService);
    app()->instance(TwilioService::class, $this->twilioService);
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]))->once();
    $this->twilioService->client()->messages = $messageListMock;

    $response = $this->call(
        $method,
        '/post-call-action.php',
        ["Digits"=>"1","To" => $this->to, "From" => $this->from,
            "Payload" => "[\"test message 1\", \"test message 2\"]"]
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'thank you for calling, goodbye</Say></Response>'], false);
})->with(['GET', 'POST']);

test('send meeting results SMS with combine with infinite searching', function ($method) {
    $_SESSION["override_sms_combine"] = true;

    $settingsService = new SettingsService();
    app()->instance(SettingsService::class, $settingsService);
    app()->instance(TwilioService::class, $this->twilioService);
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]))->once();
    $this->twilioService->client()->messages = $messageListMock;

    $response = $this->call(
        $method,
        '/post-call-action.php',
        ["Digits"=>"3","To" => $this->to, "From" => $this->from,
            "Payload" => "[\"test message 1\", \"test message 2\"]"]
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response><Redirect method="GET">https://example.org/index.php</Redirect></Response>'], false);
})->with(['GET', 'POST']);
