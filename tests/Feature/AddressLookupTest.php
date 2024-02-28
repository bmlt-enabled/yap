<?php

use App\Services\SettingsService;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('search by address for meeting information with speech text result with google api key', function ($method) {
    $response = $this->call(
        $method,
        '/address-lookup.php',
        [
            "Digits" => "Raleigh, NC",
            "SearchType" => "1",
        ]
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">searching meeting information for Raleigh, NC, USA</Say>',
            '<Redirect method="GET">meeting-search.php?Latitude=35.7795897&amp;Longitude=-78.6381787</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('search by zip code for meeting information with speech text result with google api key', function ($method) {
    $response = $this->call(
        $method,
        '/address-lookup.php',
        [
            "Digits" => "27592",
            "SearchType" => "1",
        ]
    );
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

test('search by zip code for meeting information with speech text result and pronunciation override with google api key', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set('pronunciations', [[
        "source"=>"Sequim",
        "target"=>"Skwim"
    ]]);
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call(
        $method,
        '/address-lookup.php',
        [
            "Digits" => "98382",
            "SearchType" => "2",
        ]
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">searching meeting information for Skwim, WA 98382, USA</Say>',
            '<Redirect method="GET">meeting-search.php?Latitude=48.0772936&amp;Longitude=-123.1319107</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
