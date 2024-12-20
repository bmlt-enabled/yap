<?php
test('test the blocklist with an exact match', function ($method) {
    $caller = "5557778888";
    session()->put('override_blocklist', $caller);
    $response = $this->call($method, '/', [
        "Caller"=>$caller
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
})->with(['GET', 'POST']);

test('test the blocklist without a match', function ($method) {
    $caller = "5557778888";
    session()->put('override_blocklist', $caller);
    $response = $this->call($method, '/', [
        "Caller"=>"5557778889"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertDontSee(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
})->with(['GET', 'POST']);
