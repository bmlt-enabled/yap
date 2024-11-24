<?php

test('invalid entry with province input response', function ($method) {
    session()->put('override_province_lookup_list', ["North Carolina","South Carolina"]);
    $response = $this->call($method, '/province-lookup-list-response.php', ['Digits'=>'3', 'SearchType'=>'2']);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">you might have an invalid entry</Say>',
            '<Redirect method="GET">province-voice-input.php?SearchType=2</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid entry with province input response', function ($method) {
    session()->put('override_province_lookup_list', ["North Carolina","South Carolina"]);
    $response = $this->call($method, '/province-lookup-list-response.php', ['Digits'=>'2', 'SearchType'=>'2']);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect>city-or-county-voice-input.php?SearchType=2&amp;SpeechResult=South+Carolina</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
