<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
});

test('search for volunteers by city or county', function () {
    $_REQUEST['SearchType'] = "1";
    $_REQUEST['Digits'] = "1";
    $response = $this->call('GET', '/input-method-result.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">city-or-county-voice-input.php?SearchType=1&amp;InputMethod=4</Redirect>',
            '</Response>'
        ], false);
});

test('search for volunteers by zip code', function () {
    $_REQUEST['SearchType'] = "1";
    $_REQUEST['Digits'] = "2";
    $response = $this->call('GET', '/input-method-result.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">zip-input.php?SearchType=1&amp;InputMethod=5</Redirect>',
            '</Response>'
        ], false);
});
