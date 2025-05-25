<?php

use Illuminate\Support\Facades\Artisan;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

test('database reset in production mode should fail', function ($method) {
    config(['app.env' => 'production']);
    $response = $this->call($method, '/api/resetDatabase');
    $response
        ->assertStatus(403)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            'status' => 'error',
            'message' => 'Cannot reset database in production environment.'
        ]);
})->with(['POST']);

test('database reset', function ($method) {
    $response = $this->call($method, '/api/resetDatabase');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            'status' => 'database reset',
        ]);
})->with(['POST']);
