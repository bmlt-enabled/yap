<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('initial sms gateway default', function () {
    $response = $this->call('GET', '/helpline-sms.php', [
        "OriginalCallerId" => '+19735551212',
        "To" => '+12125551212',
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
    ], false);
});
