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
