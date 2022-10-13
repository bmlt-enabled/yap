<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
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

test('jft option', function () {
    $_SESSION['override_jft_option'] = true;
    $_REQUEST['Digits'] = "3";
    $response = $this->call('GET', '/input-method-result.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">fetch-jft.php</Redirect>',
            '</Response>'
        ], false);
});

test('city or county lookup', function () {
    $_REQUEST['Digits'] = "1";
    $_REQUEST['SearchType'] = "1";
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

test('province option', function () {
    $_SESSION['override_province_lookup'] = true;
    $_REQUEST['Digits'] = "1";
    $_REQUEST['SearchType'] = "1";
    $response = $this->call('GET', '/input-method-result.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">province-voice-input.php?SearchType=1&amp;InputMethod=4</Redirect>',
            '</Response>'
        ], false);
});

test('invalid entry', function () {
    $_REQUEST['Digits'] = "5";
    $response = $this->call('GET', '/input-method-result.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">you might have an invalid entry</Say>',
            "<Redirect>index.php</Redirect>",
            '</Response>'
        ], false);
});
