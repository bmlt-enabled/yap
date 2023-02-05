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
    $response = $this->call('GET', '/input-method-result.php', [
        "SearchType"=>"1",
        "Digits"=>"1",
    ]);
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
    $response = $this->call('GET', '/input-method-result.php', [
        "SearchType" => "1",
        "Digits" => "2"
    ]);
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
    $response = $this->call('GET', '/input-method-result.php', [
        "Digits"=>"3"
    ]);
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

test('spad option', function () {
    $_SESSION['override_spad_option'] = true;
    $response = $this->call('GET', '/input-method-result.php', [
        "Digits"=>"4"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">fetch-spad.php</Redirect>',
            '</Response>'
        ], false);
});

test('city or county lookup', function () {
    $response = $this->call('GET', '/input-method-result.php', [
        "Digits" => "1",
        "SearchType" => "1"
    ]);
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
    $response = $this->call('GET', '/input-method-result.php', [
        "Digits"=>"1",
        "SearchType"=>"1"
    ]);
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
    $response = $this->call('GET', '/input-method-result.php', [
        "Digits"=>"5"
    ]);
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
