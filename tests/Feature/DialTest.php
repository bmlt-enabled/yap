<?php
beforeAll(function() {
    putenv("ENVIRONMENT=test");
});

beforeEach(function() {
    $_SERVER['REQUEST_URI'] = "/";
});

test('initial call-in default', function () {
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Pause length="2"></Pause>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>'
    ], false);
});

test('swapped menu', function () {
    $GLOBALS['digit_map_search_type'] = ['1' => SearchType::MEETINGS, '2' => SearchType::VOLUNTEERS];
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Pause length="2"></Pause>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to search for meetings</Say>',
            '<Say voice="alice" language="en-US">press two to find someone to talk to</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});
