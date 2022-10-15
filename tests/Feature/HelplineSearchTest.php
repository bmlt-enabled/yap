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
    $_REQUEST['Address'] = "Raleigh, NC";
    $_REQUEST['SearchType'] = "1";
    $response = $this->call('GET', '/helpline-search.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">input-method.php?Digits=1&amp;Retry=1&amp;RetryMessage=Couldn%27t+find+an+address+for+that+location.</Redirect>',
            '</Response>'
        ], false);
});