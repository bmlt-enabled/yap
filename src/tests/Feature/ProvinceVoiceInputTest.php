<?php

test('province input list selection', function ($method) {
    session()->put('override_province_lookup_list', ["North Carolina","South Carolina"]);
    $response = $this->call($method, '/province-voice-input.php', ["SearchType"=>2]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" hints="" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="province-lookup-list-response.php?SearchType=2" method="GET">',
            '<Say voice="alice" language="en-US">for North Carolina press one</Say>',
            '<Say voice="alice" language="en-US">for South Carolina press two</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('province input speech input', function ($method) {
    $response = $this->call($method, '/province-voice-input.php', ["SearchType"=>2]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" hints="" input="speech" timeout="10" speechTimeout="auto" action="city-or-county-voice-input.php?SearchType=2" method="GET">',
            '<Say voice="alice" language="en-US">',
            'please say the name of the state or province',
            '</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
