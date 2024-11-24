<?php

use App\Constants\SearchType;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;

    $this->callSid = "abc123";
    $this->called = "+19998887777";
});

test('search for volunteers', function ($method) {
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"1"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=1" method="GET">',
            '<Say voice="alice" language="en-US">press one to search for someone to talk to by city or suburb</Say>',
            '<Say voice="alice" language="en-US">press two to search for someone to talk to by zip code</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('search for meetings in french', function ($method) {
    $selected_language = "fr-CA";
    session()->put("override_word_language", $selected_language);
    session()->put("override_gather_language", $selected_language);
    session()->put("override_language", $selected_language);
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="fr-CA">Meeting search results will also be sent to you by SMS text message.</Say>',
            '<Gather language="fr-CA" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=2" method="GET">',
            '<Say voice="alice" language="fr-CA">faites-le un pour rechercher réunions par ville ou région</Say>',
            '<Say voice="alice" language="fr-CA">faites-le deux pour rechercher réunions par code postal</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('search for volunteers with word overrides with session', function ($method) {
    session()->put('override_city_or_county', "foo or bar");
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"1"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=1" method="GET">',
            '<Say voice="alice" language="en-US">press one to search for someone to talk to by foo or bar</Say>',
            '<Say voice="alice" language="en-US">press two to search for someone to talk to by zip code</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('search for volunteers with word overrides with querystring', function ($method) {
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"1",
        "override_city_or_county" => "bar or foo"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=1" method="GET">',
            '<Say voice="alice" language="en-US">press one to search for someone to talk to by bar or foo</Say>',
            '<Say voice="alice" language="en-US">press two to search for someone to talk to by zip code</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('search for meetings', function ($method) {
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"2",
        "CallSid"=>$this->callSid,
        "Called"=>$this->called,
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">Meeting search results will also be sent to you by SMS text message.</Say>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=2" method="GET">',
            '<Say voice="alice" language="en-US">press one to search for meetings by city or suburb</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings by zip code</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('search for volunteers, disable postal code gathering', function ($method) {
    session()->put('override_disable_postal_code_gather', true);
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"1"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">input-method-result.php?SearchType=1&amp;Digits=1</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('search for meetings, disable postal code gathering', function ($method) {
    session()->put('override_disable_postal_code_gather', true);
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">input-method-result.php?SearchType=2&amp;Digits=1</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('direct to volunteer search for a specific service body', function ($method) {
    session()->put('override_service_body_id', 44);
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"1",
        "Called"=>"123"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-search.php?Called=123&amp;ysk='.getSessionCookieValue($response).'</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('direct to volunteer search for a specific service body with postal code gather disabled', function ($method) {
    session()->put('override_disable_postal_code_gather', true);
    session()->put('override_service_body_id', 44);
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"1",
        "Called"=>"123"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-search.php?Called=123&amp;ysk='.getSessionCookieValue($response).'</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('search for volunteers without custom query', function ($method) {
    session()->put('override_custom_query', '&services=92');
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"2",
        "Called"=>"123"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">meeting-search.php?Called=123</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('jft option enabled and selected', function ($method) {
    session()->put('override_jft_option', true);
    $response = $this->call($method, '/input-method.php', [
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

test('spad option enabled and selected', function ($method) {
    session()->put('override_spad_option', true);
    $response = $this->call($method, '/input-method.php', [
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

test('dialback selected', function ($method) {
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"9"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">dialback.php</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('menu with meeting search option with jft and spad enabled', function ($method) {
    session()->put('override_spad_option', true);
    session()->put('override_jft_option', true);
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">Meeting search results will also be sent to you by SMS text message.</Say>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=2" method="GET">',
            '<Say voice="alice" language="en-US">press one to search for meetings by city or suburb</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings by zip code</Say>',
            '<Say voice="alice" language="en-US">press three to listen to the just for today</Say>',
            '<Say voice="alice" language="en-US">press four to listen to the spiritual principle a day</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('custom extension configured and selected', function ($method) {
    session()->put('override_custom_extensions', [7 => '12125551212']);
    session()->put('override_digit_map_search_type', [
        '1' => SearchType::VOLUNTEERS,
        '2' => SearchType::MEETINGS,
        '3' => SearchType::JFT,
        '4' => SearchType::SPAD,
        '7' => SearchType::CUSTOM_EXTENSIONS,
        '9' => SearchType::DIALBACK
    ]);

    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"7"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">custom-ext.php</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('play custom title', function ($method) {
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"2",
        "PlayTitle"=>"1"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">Meeting search results will also be sent to you by SMS text message.</Say>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=2" method="GET">',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to search for meetings by city or suburb</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings by zip code</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('invalid search', function ($method) {
    $response = $this->call($method, '/input-method.php', [
        "Digits"=>"5"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">you might have an invalid entry</Say>',
            '<Redirect>index.php</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('retry message loop with custom message', function ($method) {
    $response = $this->call($method, '/input-method.php', [
        "Retry"=>"1",
        "RetryMessage"=>"You Failed Son!",
        "Digits"=>"1",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=1" method="GET">',
            '<Say voice="alice" language="en-US">You Failed Son!</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">press one to search for someone to talk to by city or suburb</Say>',
            '<Say voice="alice" language="en-US">press two to search for someone to talk to by zip code</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('retry message loop with location message', function ($method) {
    $response = $this->call($method, '/input-method.php', [
        "Retry"=>"1",
        "Digits"=>"1",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=1" method="GET">',
            '<Say voice="alice" language="en-US">sorry, could not find location, please retry your entry</Say>',
            '<Pause length="1"/>',
            '<Say voice="alice" language="en-US">press one to search for someone to talk to by city or suburb</Say>',
            '<Say voice="alice" language="en-US">press two to search for someone to talk to by zip code</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
