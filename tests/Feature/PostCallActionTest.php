<?php

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

    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ]);

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]));
    $this->twilioClient->messages = $messageListMock;
    $GLOBALS['twilioClient'] = $this->twilioClient;
});

test('standard call ending', function () {
    $response = $this->call('GET', '/post-call-action.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'thank you for calling, goodbye</Say></Response>'], false);
});

test('start over', function () {
    $_SESSION["initial_webhook"] = "https://example.org/index.php";
    $_REQUEST["Digits"] = "2";
    $response = $this->call('GET', '/post-call-action.php', ["Digits"=>"2"]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response><Redirect method="GET">https://example.org/index.php</Redirect></Response>'], false);
});

test('send meeting results SMS', function () {
    $_REQUEST["Digits"] = "1";
    $response = $this->call(
        'GET',
        '/post-call-action.php',
        ["Digits" => "2", "To" => "+15005550006", "From" => "+12125551212",
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
});

test('send meeting results SMS with combine', function () {
    $_REQUEST["Digits"] = "1";
    $_SESSION["override_sms_combine"] = true;
    $response = $this->call(
        'GET',
        '/post-call-action.php',
        ["Digits"=>"2", "To" => $this->to, "From" => $this->from,
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
});
