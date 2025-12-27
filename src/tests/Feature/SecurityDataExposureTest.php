<?php

use App\Models\User;
use App\Models\RecordEvent;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

test('security: user response does not contain password hash', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);
    session()->put('auth_is_admin', true);

    // Create a user
    $this->call('POST', '/api/v1/users', [
        'name' => 'Test User',
        'username' => 'testuser',
        'password' => 'supersecretpassword',
        'permissions' => [],
        'service_bodies' => []
    ]);

    // Get user details
    $response = $this->call('GET', '/api/v1/users/testuser');
    $response->assertStatus(200);

    $json = $response->json();

    // Password should never be in response (not even hashed)
    foreach ($json as $user) {
        expect($user)->not->toHaveKey('password');
    }

    // Also check getting all users
    $allUsersResponse = $this->call('GET', '/api/v1/users');
    $allUsersResponse->assertStatus(200);

    $allUsers = $allUsersResponse->json();
    foreach ($allUsers as $user) {
        expect($user)->not->toHaveKey('password');
    }
});

test('security: voicemail response does not expose PIN', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);
    session()->put('auth_is_admin', true);
    session()->put('auth_service_bodies_rights', [1059]);

    // Create a test voicemail record with a PIN
    RecordEvent::create([
        'callsid' => 'TEST_CALLSID_' . Str::random(10),
        'service_body_id' => 1059,
        'event_id' => 1,  // Voicemail event
        'event_time' => now(),
        'meta' => json_encode([
            'pin' => '1234',  // This PIN should NOT be exposed
            'from_number' => '+15551234567',
            'to_number' => '+15559876543',
            'duration' => 30
        ])
    ]);

    // Fetch voicemails
    $response = $this->call('GET', '/api/v1/voicemail', [
        'serviceBodyId' => 1059
    ]);

    // If the endpoint returns data, check that PIN is not exposed
    if ($response->status() === 200) {
        $voicemails = $response->json();

        if (!empty($voicemails) && isset($voicemails['data'])) {
            foreach ($voicemails['data'] as $voicemail) {
                // PIN should not be in the response at all
                expect($voicemail)->not->toHaveKey('pin');

                // If meta is returned, PIN should not be in meta
                if (isset($voicemail['meta'])) {
                    $meta = is_string($voicemail['meta']) ? json_decode($voicemail['meta'], true) : $voicemail['meta'];
                    expect($meta)->not->toHaveKey('pin');
                }
            }
        }
    }
});

test('security: error responses do not leak SQL or stack traces', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);
    session()->put('auth_is_admin', true);

    // Trigger an error by requesting a non-existent user
    $response = $this->call('GET', '/api/v1/users/nonexistent_user_that_does_not_exist');

    // Get response content
    $content = $response->getContent();

    // Should not contain SQL keywords in error
    expect($content)->not->toContain('SELECT');
    expect($content)->not->toContain('INSERT');
    expect($content)->not->toContain('UPDATE');
    expect($content)->not->toContain('DELETE');
    expect($content)->not->toContain('FROM users');

    // Should not contain stack trace indicators
    expect($content)->not->toContain('Stack trace:');
    expect($content)->not->toContain('#0 /');  // Stack trace line format
    expect($content)->not->toContain('PDOException');
    expect($content)->not->toContain('QueryException');
});

test('security: unauthenticated access to voicemail endpoint is blocked', function () {
    // No authentication at all
    $response = $this->call('GET', '/api/v1/voicemail', [
        'serviceBodyId' => 1059
    ]);

    // Should return 401 or 302 (redirect to login) - both block access
    expect($response->status())->toBeIn([401, 302]);
});
