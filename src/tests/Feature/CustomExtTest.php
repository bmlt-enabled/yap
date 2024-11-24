<?php
test('custom extensions', function ($method) {
    session()->put('override_en_US_custom_extensions_greeting', "https://fake.org/test.mp3");
    $response = $this->call($method, '/custom-ext.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" finishOnKey="#" timeout="15" action="custom-ext-dialer.php" method="GET">',
            '<Play>',
            'https://fake.org/test.mp3',
            '</Play>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('custom extensions dialer', function ($method) {
    session()->put("override_custom_extensions", [365 => '555-555-1212']);
    $response = $this->call($method, '/custom-ext-dialer.php?Called=%2B17183367631&Digits=365#');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Dial callerId="+17183367631">',
            '<Number>555-555-1212</Number>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
