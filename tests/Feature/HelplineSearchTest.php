<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('force number', function () {
    $response = $this->call('GET', '/helpline-search.php', [
        'SearchType' => "1",
        'Called' => "+12125551212",
        'ForceNumber' => '+19998887777',
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Dial><Number sendDigits="w">+19998887777</Number>',
            '</Response>'
        ], false);
});


test('invalid entry', function () {
    $response = $this->call('GET', '/helpline-search.php', [
        'Address' => "Raleigh, NC",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">input-method.php?Digits=1&amp;Retry=1&amp;RetryMessage=Couldn%27t+find+an+address+for+that+location.</Redirect>',
            '</Response>'
        ], false);
});

test('valid search', function () {
    $_SESSION['override_service_body_id'] = 44;
    $response = $this->call('GET', '/helpline-search.php', [
        'Address' => "Raleigh, NC",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'stub_google_maps_endpoint' => true
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please wait while we connect your call</Say>',
            '<Dial>',
            '<Conference waitUrl="https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical"',
            'statusCallback="helpline-dialer.php?service_body_id=44&amp;Caller=+12125551212"',
            'startConferenceOnEnter="false"',
            'endConferenceOnExit="true"',
            'statusCallbackMethod="GET"',
            'statusCallbackEvent="start join end leave"',
            'waitMethod="GET"',
            'beep="false">',
            '</Conference>',
            '</Dial>',
            '</Response>'
        ], false);
});
