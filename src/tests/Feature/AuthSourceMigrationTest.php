<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\FakeHttp;

/*
 * 2026_11_03_000000_add_auth_source_to_users_table (bmlt-enabled/yap#1674):
 * rows a 5.0 beta created for BMLT logins lose the sha256(<BMLT password>)
 * they stored, and every token issued under the old login rules is revoked.
 */

const AUTH_SOURCE_MIGRATION = 'database/migrations/2026_11_03_000000_add_auth_source_to_users_table.php';

beforeEach(function () {
    FakeHttp::install();
});

function rollbackAuthSourceMigration(): void
{
    Artisan::call('migrate:rollback', ['--path' => AUTH_SOURCE_MIGRATION, '--force' => true]);
}

function runAuthSourceMigration(): void
{
    Artisan::call('migrate', ['--path' => AUTH_SOURCE_MIGRATION, '--force' => true]);
}

function insertBetaUser(string $name, string $username, string $password, int $isAdmin, int $permissions): void
{
    DB::table('users')->insert([
        'name' => $name,
        'username' => $username,
        'password' => hash('sha256', $password),
        'is_admin' => $isAdmin,
        'permissions' => $permissions,
        'service_bodies' => '1',
    ]);
}

test('rows a beta BMLT login created lose their stored password', function () {
    rollbackAuthSourceMigration();
    expect(\Illuminate\Support\Facades\Schema::hasColumn('users', 'auth_source'))->toBeFalse();

    // What the beta's firstOrCreate wrote: name = username, no admin, no permissions.
    insertBetaUser('old_bmlt_user', 'old_bmlt_user', 'bmlt-password', 0, 0);

    runAuthSourceMigration();

    $row = DB::table('users')->where('username', 'old_bmlt_user')->first();
    expect($row->auth_source)->toBe(User::AUTH_SOURCE_BMLT);
    expect($row->password)->toBe('');

    // The stored hash no longer logs in; only BMLT can (and the fake BMLT
    // does not know this account).
    $this->postJson('/api/v1/login', ['username' => 'old_bmlt_user', 'password' => 'bmlt-password'])
        ->assertStatus(401);
});

test('local accounts keep their password', function () {
    rollbackAuthSourceMigration();

    insertBetaUser('admin', 'admin', 'admin', 1, 0);           // the default admin
    insertBetaUser('Jane Doe', 'jane', 'jane-password', 0, 0); // a regular user
    insertBetaUser('ops', 'ops', 'ops-password', 0, 1);        // has yap permissions

    runAuthSourceMigration();

    foreach (['admin' => 'admin', 'jane' => 'jane-password', 'ops' => 'ops-password'] as $username => $password) {
        $row = DB::table('users')->where('username', $username)->first();
        expect($row->auth_source)->toBe(User::AUTH_SOURCE_LOCAL);
        expect($row->password)->toBe(hash('sha256', $password));
    }
});

test('existing tokens are revoked', function () {
    $user = User::factory()->admin()->create();
    $token = $user->createToken('API Token')->plainTextToken;

    rollbackAuthSourceMigration();
    runAuthSourceMigration();

    expect(DB::table('personal_access_tokens')->count())->toBe(0);
    $this->withToken($token)->getJson('/api/v1/user')->assertStatus(401);
});
