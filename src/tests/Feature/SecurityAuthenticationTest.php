<?php

use App\Models\User;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

test('security: rate limits after multiple failed login attempts', function () {
    User::saveUser('Test', 'testuser', 'correctpass', [], []);

    // Attempt 10 rapid failed logins
    for ($i = 0; $i < 10; $i++) {
        $this->post('/api/v1/login', [
            'username' => 'testuser',
            'password' => 'wrongpass'
        ]);
    }

    // 11th attempt should be rate limited (429)
    $response = $this->post('/api/v1/login', [
        'username' => 'testuser',
        'password' => 'correctpass'
    ]);

    $response->assertStatus(429);
});

test('security: invalid token is rejected', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer invalid-garbage-token-12345',
    ])->getJson('/api/v1/users');

    $response->assertStatus(401);
});

test('security: token invalidated after manual deletion', function () {
    // Create user and login
    User::saveUser('Test', 'testuser', 'testpass', [], []);

    $loginResponse = $this->post('/api/v1/login', [
        'username' => 'testuser',
        'password' => 'testpass'
    ]);
    $loginResponse->assertStatus(200);

    $token = $loginResponse->json('token');

    // Verify token works
    $verifyResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/settings');
    $verifyResponse->assertStatus(200);

    // Manually delete all tokens for the user (simulating what logout should do)
    $user = User::where('username', 'testuser')->first();
    $user->tokens()->delete();

    // Token should no longer work
    $afterDeletionResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/settings');
    $afterDeletionResponse->assertStatus(401);
});

test('security: missing token rejected on protected endpoint', function () {
    // No Authorization header at all
    $response = $this->getJson('/api/v1/users');

    $response->assertStatus(401);
});

test('security: database reset blocked in production environment', function () {
    // The endpoint checks config('app.env'), so we need to set that
    config(['app.env' => 'production']);

    $response = $this->post('/api/resetDatabase');

    // Should return 403 in production
    $response->assertStatus(403);
    $response->assertJson([
        'status' => 'error',
        'message' => 'Cannot reset database in production environment.'
    ]);

    // Restore test environment
    config(['app.env' => 'testing']);
});
