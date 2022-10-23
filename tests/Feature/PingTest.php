<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('ping with php extension', function () {
    $response = $this->call('GET', '/ping.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/plain; charset=UTF-8")
        ->assertSeeText("PONG", false);
});

test('ping without php extension', function () {
    $response = $this->call('GET', '/ping');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/plain; charset=UTF-8")
        ->assertSeeText("PONG", false);
});
