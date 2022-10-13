<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('search for volunteers', function () {
    $_REQUEST['Digits'] = "1";
    $response = $this->call('GET', '/input-method.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=1" method="GET">',
            '<Say voice="alice" language="en-US">press one to search for someone to talk to by city or county</Say>',
            '<Say voice="alice" language="en-US">press two to search for someone to talk to by zip code</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('search for meetings', function () {
    $_REQUEST['Digits'] = "2";
    $response = $this->call('GET', '/input-method.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=2" method="GET">',
            '<Say voice="alice" language="en-US">press one to search for meetings by city or county</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings by zip code</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('search for meetings, disable postal code gathering', function () {
    $_SESSION['override_disable_postal_code_gather'] = true;
    $_REQUEST['Digits'] = "2";
    $response = $this->call('GET', '/input-method.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">input-method-result.php?SearchType=2&amp;Digits=1</Redirect>',
            '</Response>'
        ], false);
});

test('direct to volunteer search for a specific service body', function () {
    $_SESSION['override_service_body_id'] = 44;
    $_REQUEST['Digits'] = "1";
    $_REQUEST['Called'] = "123";
    $response = $this->call('GET', '/input-method.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-search.php?Called=123</Redirect>',
            '</Response>'
        ], false);
});

test('search for volunteers without custom query', function () {
    $_SESSION['override_custom_query'] = '&services=92';
    $_REQUEST['Digits'] = "2";
    $_REQUEST['Called'] = "123";
    $response = $this->call('GET', '/input-method.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">meeting-search.php?Called=123</Redirect>',
            '</Response>'
        ], false);
});

test('jft option enabled and selected', function () {
    $_SESSION['override_jft_option'] = true;
    $_REQUEST['Digits'] = "3";
    $response = $this->call('GET', '/input-method.php');
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

test('voicemail playback selected', function () {
    $_REQUEST['Digits'] = "8";
    $response = $this->call('GET', '/input-method.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">voicemail-playback.php</Redirect>',
            '</Response>'
        ], false);
});

test('dialback selected', function () {
    $_REQUEST['Digits'] = "9";
    $response = $this->call('GET', '/input-method.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">dialback.php</Redirect>',
            '</Response>'
        ], false);
});

test('custom extension configured and selected', function () {
    $_REQUEST['Digits'] = "7";
    $_SESSION['override_custom_extensions'] = [7 => '12125551212'];
    $_SESSION['override_digit_map_search_type'] = [
        '1' => SearchType::VOLUNTEERS,
        '2' => SearchType::MEETINGS,
        '3' => SearchType::JFT,
        '7' => SearchType::CUSTOM_EXTENSIONS,
        '8' => SearchType::VOICEMAIL_PLAYBACK,
        '9' => SearchType::DIALBACK
    ];

    $response = $this->call('GET', '/input-method.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">custom-ext.php</Redirect>',
            '</Response>'
        ], false);
});

test('invalid search', function () {
    $_REQUEST['Digits'] = "5";
    $response = $this->call('GET', '/input-method.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">you might have an invalid entry</Say>',
            '<Redirect>index.php</Redirect>',
            '</Response>'
        ], false);
});
