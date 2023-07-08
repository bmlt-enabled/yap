<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('test the blocklist with an exact match', function () {
    $caller = "5557778888";
    $_SESSION['override_blocklist'] = $caller;
    $response = $this->call('GET', '/', [
        "Caller"=>$caller
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
});

test('test the blocklist without a match', function () {
    $caller = "5557778888";
    $_SESSION['override_blocklist'] = $caller;
    $response = $this->call('GET', '/', [
        "Caller"=>"5557778889"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertDontSee(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
});
