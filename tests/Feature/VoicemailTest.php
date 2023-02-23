<?php

use Tests\FakeTwilioHttpClient;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    new \Tests\TwilioMessagesCreateMock();
});

test('voicemail standard response', function () {
    $_SESSION['override_service_body_id'] = "44";
    $response = $this->call('GET', '/voicemail.php', [
        "caller_id" => "+17325551212",
        "Caller" => "+12125551313",
        "ysk" => "test"
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please leave a message after the tone, hang up when finished</Say>',
            '<Record playBeep="1" maxLength="120" timeout="15" recordingStatusCallback="voicemail-complete.php?service_body_id=44&amp;caller_id=%2B17325551212&amp;caller_number=%2B12125551313" recordingStatusCallbackMethod="GET"/>',
            '</Response>'
        ], false);
});

test('voicemail custom prompt', function () {
    $_SESSION['override_service_body_id'] = "44";
    $_SESSION['override_en_US_voicemail_greeting'] = "https://example.org/test.mp3";
    $response = $this->call('GET', '/voicemail.php', [
        "caller_id" => "+17325551212",
        "Caller" => "+12125551313",
        "ysk" => "test"
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Play>https://example.org/test.mp3</Play>',
            '<Record playBeep="1" maxLength="120" timeout="15" recordingStatusCallback="voicemail-complete.php?service_body_id=44&amp;caller_id=%2B17325551212&amp;caller_number=%2B12125551313" recordingStatusCallbackMethod="GET"/>',
            '</Response>'
        ], false);
});

