<?php

use Illuminate\Support\Facades\Session;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('clear the session', function () {
    Session::put("override_blah", "test");
    $this->assertEquals("test", Session::get("override_blah"));
    $response = $this->get('/v1/session/delete');
    $response->assertStatus(200)
        ->assertHeader("Content-Type", "text/plain; charset=UTF-8")
        ->assertSeeText("OK");
    $this->assertNull(Session::get("override_blah"));
});
