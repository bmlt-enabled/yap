<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('clear the session', function () {
    $_SESSION["override_blah"] = "test";
    assert($_SESSION["override_blah"] == "test");
    $response = $this->call('GET', '/v1/session/delete');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/plain; charset=UTF-8")
        ->assertSeeText("OK", false);
    assert(!isset($_SESSION["override_blah"]));
});
