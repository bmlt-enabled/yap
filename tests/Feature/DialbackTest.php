<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('dialback initial', function () {
    $response = $this->get('/dialback.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" timeout="15" finishOnKey="#" action="dialback-dialer.php" method="GET">',
            '<Say voice="alice" language="en-US">',
            'Please enter the dialback pin, followed by the pound sign.',
            '</Say>',
            '</Gather>',
            '</Response>'
    ], false);
});
