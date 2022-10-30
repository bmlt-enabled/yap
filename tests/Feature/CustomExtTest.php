<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('custom extensions', function () {
    $GLOBALS['en_US_custom_extensions_greeting'] = "https://fake.org/test.mp3";
    $response = $this->call('GET', '/custom-ext.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" finishOnKey="#" timeout="15" action="custom-ext-dialer.php" method="GET">',
            '<Play>',
            'https://fake.org/test.mp3',
            '</Play>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('custom extensions dialer', function () {
    $_SESSION["override_custom_extensions"] = [365 => '555-555-1212'];
    $response = $this->call('GET', '/custom-ext-dialer.php?Called=%2B17183367631&Digits=365#');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Dial callerId="+17183367631">',
            '<Number>555-555-1212</Number>',
            '</Dial>',
            '</Response>'
        ], false);
});
