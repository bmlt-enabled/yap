<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
});

test('initial call-in default', function () {
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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

test('initial call-in default with language selections', function () {
    $_SESSION['override_language_selections'] = "en-US,es-US";
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response><Redirect>lng-selector.php</Redirect></Response>',
        ], false);
});
