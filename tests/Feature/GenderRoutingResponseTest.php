<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('invalid entry', function () {
    $_REQUEST['SearchType'] = "1";
    $_REQUEST['Digits'] = "7";
    $response = $this->call('GET', '/gender-routing-response.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">you might have an invalid entry</Say>',
            '<Redirect>gender-routing.php</Redirect>',
            '</Response>'
        ], false);
});
