<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('invalid entry', function () {
    $_REQUEST['Address'] = "Raleigh, NC";
    $_REQUEST['SearchType'] = "1";
    $_REQUEST['Called'] = "+12125551212";
    $response = $this->call('GET', '/helpline-search.php');
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
    $_REQUEST['stub_google_maps_endpoint'] = true;
    $_REQUEST['Address'] = "Raleigh, NC";
    $_REQUEST['SearchType'] = "1";
    $_REQUEST['Called'] = "+12125551212";
    $response = $this->call('GET', '/helpline-search.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '        <Say voice="alice" language="en-US">',
            'please wait while we connect your call            </Say>',
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
