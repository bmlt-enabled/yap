<?php

use App\Constants\AuthMechanism;
use App\Models\User;
use App\Services\RootServerService;
use Laravel\Sanctum\Sanctum;
use Tests\RootServerMocks;

beforeEach(function () {
    $this->rootServerMocks = new RootServerMocks();
});

test('get service bodies', function () {
    Sanctum::actingAs(User::factory()->create());
    ;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $response = $this->call('GET', '/api/v1/rootServer/serviceBodies');
    $response
        ->assertJsonIsArray()
        ->assertStatus(200);
});

test('get service bodies no auth', function () {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $response = $this->call('GET', '/api/v1/rootServer/serviceBodies');
    $response
        ->assertHeader("Location", "http://localhost/api/v1/login")
        ->assertHeader("Content-Type", "text/html; charset=utf-8")
        ->assertStatus(302);
});
