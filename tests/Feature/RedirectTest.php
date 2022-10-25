<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('service body extension response', function () {
    $_REQUEST['Digits'] = 1;
    $response = $this->call('GET', '/service-body-ext-response.php?Digits=1');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect>helpline-search.php?override_service_body_id=1</Redirect>',
            '</Response>'
        ], false);
});
