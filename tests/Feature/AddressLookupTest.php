<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('search for address with speech text result with bad google api key', function () {
    $_REQUEST['SpeechResult'] = "Raleigh, NC";
    $_REQUEST['SearchType'] = "1";
    $response = $this->call('GET', '/address-lookup.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">input-method.php?Digits=1&amp;Retry=1</Redirect>',
            '</Response>'
        ], false);
});
