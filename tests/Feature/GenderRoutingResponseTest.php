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
    $response = $this->call('GET', '/gender-routing-response.php?SearchType=1&Digits=7');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">you might have an invalid entry</Say>',
            '<Redirect method="GET">gender-routing.php</Redirect>',
            '</Response>'
        ], false);
});

test('selected option', function () {
    $_REQUEST['SearchType'] = "1";
    $_REQUEST['Digits'] = "1";
    $response = $this->call('GET', '/gender-routing-response.php?Digits=1&SearchType=1');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-search.php?SearchType=1</Redirect>',
            '</Response>'
        ], false);
});
