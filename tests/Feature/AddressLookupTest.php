<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('search for address with speech text result with bad google api key', function ($method) {
    $_REQUEST['stub_google_maps_endpoint'] = false;
    $response = $this->call($method, '/address-lookup.php?SpeechResult=Raleigh, NC&SearchType=1');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">input-method.php?Digits=1&amp;Retry=1</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('search for address for someone to talk to with speech text result with google api key', function ($method) {
    $_REQUEST['stub_google_maps_endpoint'] = true;
    $response = $this->call($method, '/address-lookup.php?SpeechResult=Raleigh, NC&SearchType=1');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">searching meeting information for Willow Spring, NC 27592, USA</Say>',
            '<Redirect method="GET">meeting-search.php?Latitude=35.5648713&amp;Longitude=-78.6682395</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
