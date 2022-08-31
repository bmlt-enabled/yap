<?php
beforeAll(function() {
    putenv("ENVIRONMENT=test");
});

beforeEach(function() {
    $_SERVER['REQUEST_URI'] = "/";
});

test('initial call-in default', function () {
    $_REQUEST['Digits'] = "1";
    $response = $this->call('GET', '/input-method.php');
    $response
        ->assertStatus(200)
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
