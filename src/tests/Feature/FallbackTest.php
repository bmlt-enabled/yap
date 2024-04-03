<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('get fallback', function ($method) {
    $response = $this->call(
        $method,
        '/fallback.php',
        [
            'helpline_fallback' => '+12125551212',
        ]
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'there seems to be a problem... please wait while we connect your call... please stand by.',
            '</Say>',
            '<Dial>',
            '<Number sendDigits="w">+12125551212</Number>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
