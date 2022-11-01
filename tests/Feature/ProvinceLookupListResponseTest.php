<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('invalid entry with province input response', function () {
    $_SESSION['override_province_lookup_list'] = ["North Carolina","South Carolina"];
    $_REQUEST['Digits'] = 3;
    $_REQUEST['SearchType'] = 2;
    $response = $this->call('GET', '/province-lookup-list-response.php?Digits=3&SearchType=2');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">you might have an invalid entry</Say>',
            '</Response>'
        ], false);
});

test('valid entry with province input response', function () {
    $_SESSION['override_province_lookup_list'] = ["North Carolina","South Carolina"];
    $_REQUEST['Digits'] = 2;
    $_REQUEST['SearchType'] = 2;
    $response = $this->call('GET', '/province-lookup-list-response.php?SearchType=2&Digits=2');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect>city-or-county-voice-input.php?SearchType=2&amp;SpeechResult=South+Carolina</Redirect>',
            '</Response>'
        ], false);
});
