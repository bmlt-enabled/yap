<?php

use Tests\FakeTwilioHttpClient;

beforeEach(function () {
    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
});

test('search for volunteers by city or county', function ($method) {
    $response = $this->call($method, '/input-method-result.php', [
        "SearchType"=>"1",
        "Digits"=>"1",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">city-or-county-voice-input.php?SearchType=1&amp;InputMethod=4</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('search for volunteers by zip code', function ($method) {
    $response = $this->call($method, '/input-method-result.php', [
        "SearchType" => "1",
        "Digits" => "2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">zip-input.php?SearchType=1&amp;InputMethod=5</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('jft option', function ($method) {
    session()->put('override_jft_option', true);
    $response = $this->call($method, '/input-method-result.php', [
        "Digits"=>"3"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">fetch-jft.php</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('spad option', function ($method) {
    session()->put('override_spad_option', true);
    $response = $this->call($method, '/input-method-result.php', [
        "Digits"=>"4"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">fetch-spad.php</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('city or county lookup', function () {
    $response = $this->call('GET', '/input-method-result.php', [
        "Digits" => "1",
        "SearchType" => "1"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">city-or-county-voice-input.php?SearchType=1&amp;InputMethod=4</Redirect>',
            '</Response>'
        ], false);
});

test('province option', function () {
    session()->put('override_province_lookup', true);
    $response = $this->call('GET', '/input-method-result.php', [
        "Digits"=>"1",
        "SearchType"=>"1"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">province-voice-input.php?SearchType=1&amp;InputMethod=4</Redirect>',
            '</Response>'
        ], false);
});

test('invalid entry', function () {
    $response = $this->call('GET', '/input-method-result.php', [
        "Digits"=>"5"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">you might have an invalid entry</Say>',
            "<Redirect>index.php</Redirect>",
            '</Response>'
        ], false);
});
