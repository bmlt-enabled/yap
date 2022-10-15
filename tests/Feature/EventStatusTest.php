<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

//
//test('returns data', function () {
//    $response = $this->get('/v1/events/status');
//    $response
//        ->assertStatus(200)
//        ->assertHeader("Content-Type", "application/json");
//});
