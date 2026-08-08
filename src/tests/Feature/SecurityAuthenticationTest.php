<?php

use App\Models\User;
use Tests\FakeHttp;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    // Failed logins fall through to the BMLT root server. The rate-limit test
    // below makes several of them, so FakeHttp avoids live POSTs per run.
    FakeHttp::install();
});

test('security: rate limits after multiple failed login attempts', function () {
    User::saveUser('Test', 'testuser', 'correctpass', [], []);

    // Attempt 5 rapid failed logins (login route limit is 5 per minute)
    for ($i = 0; $i < 5; $i++) {
        $this->post('/api/v1/login', [
            'username' => 'testuser',
            'password' => 'wrongpass'
        ]);
    }

    // 6th attempt should be rate limited (429)
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

test('security: logout revokes sanctum token', function () {
    User::saveUser('Test', 'testuser', 'testpass', [], []);

    $loginResponse = $this->post('/api/v1/login', [
        'username' => 'testuser',
        'password' => 'testpass'
    ]);
    $loginResponse->assertStatus(200);

    $token = $loginResponse->json('token');

    $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson('/api/v1/logout')->assertStatus(200);

    auth()->forgetGuards();

    $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/settings')->assertStatus(401);
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

    // Clear auth cache to ensure fresh token validation
    auth()->forgetGuards();

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
