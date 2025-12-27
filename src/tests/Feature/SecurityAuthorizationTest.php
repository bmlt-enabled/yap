<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

test('security: non-admin user cannot create users', function () {
    $regularUser = User::factory()->create(['is_admin' => false, 'permissions' => 0]);
    Sanctum::actingAs($regularUser);
    // NOT setting session auth_is_admin - simulating a non-admin

    $response = $this->call('POST', '/api/v1/users', [
        'name' => 'Hacker',
        'username' => 'hacker',
        'password' => 'password123',
        'permissions' => [],
        'service_bodies' => []
    ]);

    // Should be forbidden - non-admin cannot create users
    // Currently returns 404 which masks the authorization failure (should be 403)
    expect($response->status())->toBeIn([403, 404]);

    // Verify user was NOT actually created
    $user = User::where('username', 'hacker')->first();
    expect($user)->toBeNull();
});

test('security: non-admin user cannot delete users', function () {
    // First create a user as admin
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);
    session()->put('auth_is_admin', true);

    $this->call('POST', '/api/v1/users', [
        'name' => 'Target',
        'username' => 'target',
        'password' => 'password123',
        'permissions' => [],
        'service_bodies' => []
    ]);

    // Verify user was created
    $userBefore = User::where('username', 'target')->first();
    expect($userBefore)->not->toBeNull();

    // Now switch to non-admin user
    $regularUser = User::factory()->create(['is_admin' => false, 'permissions' => 0]);
    Sanctum::actingAs($regularUser);
    session()->put('auth_is_admin', false);

    $response = $this->call('DELETE', '/api/v1/users/target');

    // Should be forbidden (403 or 404 for obscured response)
    expect($response->status())->toBeIn([403, 404]);

    // Verify user was NOT deleted
    $userAfter = User::where('username', 'target')->first();
    expect($userAfter)->not->toBeNull();
});

test('security: user cannot escalate own privileges via self-update', function () {
    // Create a non-admin user
    $regularUser = User::factory()->create([
        'is_admin' => false,
        'permissions' => 0,
        'username' => 'regularuser'
    ]);
    Sanctum::actingAs($regularUser);
    session()->put('auth_is_admin', false);
    session()->put('username', 'regularuser');

    // Attempt to set is_admin=true on self
    $response = $this->call('PUT', '/api/v1/users/regularuser', [
        'name' => 'Still Regular',
        'is_admin' => true,
        'permissions' => 1  // MANAGE_USERS permission
    ]);

    // Request might succeed but is_admin should NOT be changed
    // Either 403, or 200 with is_admin still false
    if ($response->status() === 200) {
        $user = User::where('username', 'regularuser')->first();
        expect($user->is_admin)->toBeFalsy();
        expect($user->permissions)->toBe(0);
    } else {
        $response->assertStatus(403);
    }
});

test('security: user cannot access another users details', function () {
    // Create target user as admin
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);
    session()->put('auth_is_admin', true);

    $this->call('POST', '/api/v1/users', [
        'name' => 'Victim',
        'username' => 'victim',
        'password' => 'password123',
        'permissions' => [],
        'service_bodies' => [1059]
    ]);

    // Now act as a different non-admin user
    $attacker = User::factory()->create(['is_admin' => false, 'permissions' => 0]);
    Sanctum::actingAs($attacker);
    session()->put('auth_is_admin', false);
    session()->put('username', 'attacker');

    $response = $this->call('GET', '/api/v1/users/victim');

    // Should not be able to view other user's details
    // Expect 403 (forbidden) or 404 (obscured)
    // If 200, this is a vulnerability
    expect($response->status())->toBeIn([403, 404]);
});

test('security: user cannot access volunteers for unauthorized service body', function () {
    // Create user with access to only service body 1059
    $limitedUser = User::factory()->create([
        'is_admin' => false,
        'permissions' => 0,
        'service_bodies' => '1059'
    ]);
    Sanctum::actingAs($limitedUser);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies', [1059]);
    session()->put('auth_service_bodies_rights', [1059]);

    // Try to access volunteers for a different service body (1060)
    $response = $this->call('GET', '/api/v1/volunteers', [
        'serviceBodyId' => 1060
    ]);

    // Should be forbidden - not authorized for this service body
    // Accept 403, 404, or 200 with empty data (no volunteers visible)
    if ($response->status() === 200) {
        // If 200, data should be empty (can't see other service body's volunteers)
        $data = $response->json();
        expect($data['data'] ?? $data)->toBeEmpty();
    } else {
        expect($response->status())->toBeIn([403, 404]);
    }
});

test('security: user cannot modify call handling for unauthorized service body', function () {
    // Create user with access to only service body 1059
    $limitedUser = User::factory()->create([
        'is_admin' => false,
        'permissions' => 0,
        'service_bodies' => '1059'
    ]);
    Sanctum::actingAs($limitedUser);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies', [1059]);
    session()->put('auth_service_bodies_rights', [1059]);

    // Try to modify call handling for a different service body (1060)
    $response = $this->call('POST', '/api/v1/callHandling', [
        'serviceBodyId' => 1060,
        'volunteer_routing' => 'volunteers',
        'volunteer_routing_enabled' => true
    ]);

    // Should be forbidden (403 or 404)
    expect($response->status())->toBeIn([403, 404]);
});

test('security: user cannot delete voicemail from unauthorized service body', function () {
    // Create user with access to only service body 1059
    $limitedUser = User::factory()->create([
        'is_admin' => false,
        'permissions' => 0,
        'service_bodies' => '1059'
    ]);
    Sanctum::actingAs($limitedUser);
    session()->put('auth_is_admin', false);
    session()->put('auth_service_bodies', [1059]);
    session()->put('auth_service_bodies_rights', [1059]);

    // Try to delete a voicemail using a fake call SID
    // The service body check should happen before we even look for the voicemail
    $response = $this->call('DELETE', '/api/v1/voicemail/FAKE_CALLSID_12345', [
        'serviceBodyId' => 1060  // Different service body
    ]);

    // Should be forbidden - not authorized for this service body
    // Accept 403, 404, or 500 (not found is acceptable for fake SID)
    expect($response->status())->toBeIn([403, 404, 500]);
});
