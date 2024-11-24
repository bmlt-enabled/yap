<?php

use App\Constants\AuthMechanism;
use App\Services\RootServerService;
use Tests\RootServerMocks;

beforeEach(function () {
    $this->rootServerMocks = new RootServerMocks();
});

test('get service bodies', function () {
    session()->put('auth_mechanism', AuthMechanism::V2);
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $response = $this->call('GET', '/api/v1/rootServer/servicebodies');
    $response
        ->assertJsonIsArray()
        ->assertStatus(200);
});

test('get service bodies no auth', function () {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $response = $this->call('GET', '/api/v1/rootServer/servicebodies');
    $response
        ->assertHeader("Location", "http://localhost/admin")
        ->assertHeader("Content-Type", "text/html; charset=utf-8")
        ->assertStatus(302);
});
