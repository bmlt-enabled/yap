<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

test('security: SQL injection in login username does not bypass auth', function () {
    // Create a legitimate user
    User::saveUser('Legit User', 'legituser', 'correctpassword', [], []);

    // Common SQL injection payloads
    $sqlInjectionPayloads = [
        "' OR '1'='1",
        "' OR '1'='1' --",
        "' OR '1'='1' /*",
        "admin'--",
        "' UNION SELECT * FROM users --",
        "1; DROP TABLE users;--",
        "' OR 1=1 --",
    ];

    foreach ($sqlInjectionPayloads as $payload) {
        $response = $this->post('/api/v1/login', [
            'username' => $payload,
            'password' => 'anything'
        ]);

        // Should always return 401 - injection should not bypass auth
        $response->assertStatus(401);
    }
});

test('security: SQL injection in login password does not bypass auth', function () {
    // Create a legitimate user
    User::saveUser('Legit User', 'legituser', 'correctpassword', [], []);

    $sqlInjectionPayloads = [
        "' OR '1'='1",
        "' OR '1'='1' --",
        "password' OR '1'='1",
    ];

    foreach ($sqlInjectionPayloads as $payload) {
        $response = $this->post('/api/v1/login', [
            'username' => 'legituser',
            'password' => $payload
        ]);

        // Should return 401 - injection should not bypass auth
        $response->assertStatus(401);
    }
});

test('security: SQL injection in service body ID parameter is rejected', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);
    session()->put('auth_is_admin', true);

    $sqlInjectionPayloads = [
        "1 OR 1=1",
        "1; DROP TABLE users;--",
        "1 UNION SELECT * FROM users",
        "1' OR '1'='1",
    ];

    foreach ($sqlInjectionPayloads as $payload) {
        $response = $this->call('GET', '/api/v1/volunteers', [
            'serviceBodyId' => $payload
        ]);

        // Should return 400 (bad request) or empty results, not 500 (SQL error)
        expect($response->status())->not->toBe(500);
    }
});

test('security: mass assignment does not allow setting is_admin when creating user', function () {
    // Create admin to make the API call
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);
    session()->put('auth_is_admin', true);

    // Create a new user, trying to inject is_admin=true
    // (The API should NOT allow setting is_admin directly via input)
    $response = $this->call('POST', '/api/v1/users', [
        'name' => 'Should Not Be Admin',
        'username' => 'notadmin',
        'password' => 'password123',
        'permissions' => [],
        'service_bodies' => [],
        'is_admin' => true  // Attempting mass assignment
    ]);

    $response->assertStatus(200);

    // Verify the user was NOT created as admin
    $user = User::where('username', 'notadmin')->first();
    expect($user)->not->toBeNull();
    expect($user->is_admin)->toBeFalsy();
});
