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

test('return playlist', function ($method) {
    $response = $this->call($method, '/playlist.php?items=fake-1.mp3,fake-2.mp3');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Play>fake-1.mp3</Play>',
            '<Play>fake-2.mp3</Play>',
            '<Redirect>playlist.php?items=fake-1.mp3,fake-2.mp3</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
