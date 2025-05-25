<?php

use App\Services\SettingsService;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

test('test the blocklist with an exact match', function ($method) {
    $caller = "+15557778888";
    $settingsService = new SettingsService();
    $settingsService->set("blocklist", $caller);
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call($method, '/', [
        "Caller"=>$caller
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
})->with(['GET', 'POST']);

test('test the blocklist with multiple items and an exact match', function ($method) {
    $caller = "+15557778888,+15557778890";
    $settingsService = new SettingsService();
    $settingsService->set("blocklist", $caller);
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call($method, '/', [
        "Caller"=>"+15557778890"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
})->with(['GET', 'POST']);

test('test the blocklist without a match', function ($method) {
    $caller = "+15557778888";
    $settingsService = new SettingsService();
    $settingsService->set("blocklist", $caller);
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call($method, '/', [
        "Caller"=>"5557778889"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertDontSee(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
})->with(['GET', 'POST']);

test('test the blocklist with whitespace in caller number', function ($method) {
    $caller = "+15557778888";
    $settingsService = new SettingsService();
    $settingsService->set("blocklist", $caller);
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call($method, '/', [
        "Caller" => " 15557778888 "
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
})->with(['GET', 'POST']);

test('test the blocklist with whitespace in blocklist entries', function ($method) {
    $caller = " +15557778888 , +15557778890 ";
    $settingsService = new SettingsService();
    $settingsService->set("blocklist", $caller);
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call($method, '/', [
        "Caller" => "+15557778890"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
})->with(['GET', 'POST']);

test('test the blocklist with missing plus sign in caller number', function ($method) {
    $caller = "+15557778888";
    $settingsService = new SettingsService();
    $settingsService->set("blocklist", $caller);
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call($method, '/', [
        "Caller" => "15557778888"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
})->with(['GET', 'POST']);

test('test the blocklist with missing plus sign in blocklist entries', function ($method) {
    $caller = "15557778888,15557778890";
    $settingsService = new SettingsService();
    $settingsService->set("blocklist", $caller);
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call($method, '/', [
        "Caller" => "+15557778890"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
})->with(['GET', 'POST']);

test('test the blocklist with empty blocklist', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set("blocklist", "");
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call($method, '/', [
        "Caller" => "+15557778888"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertDontSee(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
})->with(['GET', 'POST']);

test('test the blocklist with missing Caller parameter', function ($method) {
    $caller = "+15557778888";
    $settingsService = new SettingsService();
    $settingsService->set("blocklist", $caller);
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call($method, '/', []);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertDontSee(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
})->with(['GET', 'POST']);

test('test the blocklist with partial match', function ($method) {
    $caller = "+15557778888";
    $settingsService = new SettingsService();
    $settingsService->set("blocklist", $caller);
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call($method, '/', [
        "Caller" => "+1555777888899"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertDontSee(["<?xml version='1.0' encoding='UTF-8'?><Response><Reject/></Response>"], false);
})->with(['GET', 'POST']);
