<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('standard call ending', function () {
    $response = $this->call('GET', '/post-call-action.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'thank you for calling, goodbye</Say></Response>'], false);
});

test('start over', function () {
    $_SESSION["initial_webhook"] = "https://example.org/index.php";
    $response = $this->call('GET', '/post-call-action.php', ["Digits"=>"2"]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'thank you for calling, goodbye</Say></Response>'], false);
});
