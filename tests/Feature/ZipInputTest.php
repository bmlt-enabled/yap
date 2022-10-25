<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('zip input for helpline lookup', function () {
    $response = $this->call('GET', '/zip-input.php?SearchType=1');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather input="dtmf" numDigits="5" timeout="10" speechTimeout="auto" action="helpline-search.php?SearchType=1" method="GET">',
            '<Say voice="alice" language="en-US">',
            'please enter your five digit zip code',
            '</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('zip input for address lookup', function () {
    $response = $this->call('GET', '/zip-input.php?SearchType=2');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather input="dtmf" numDigits="5" timeout="10" speechTimeout="auto" action="address-lookup.php?SearchType=2" method="GET">',
            '<Say voice="alice" language="en-US">',
            'please enter your five digit zip code',
            '</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('zip input for 4 digit postal code', function () {
    $GLOBALS["override_please_enter_your_digit"] = "please enter your four digit";
    $GLOBALS["override_zip_code"] = "postal code";
    $_SESSION["override_postal_code_length"] = 4;
    $response = $this->call('GET', '/zip-input.php?SearchType=2');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather input="dtmf" numDigits="4" timeout="10" speechTimeout="auto" action="address-lookup.php?SearchType=2" method="GET">',
            '<Say voice="alice" language="en-US">',
            'please enter your four digit postal code',
            '</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});
