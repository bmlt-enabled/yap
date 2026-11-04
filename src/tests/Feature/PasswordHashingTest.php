<?php

/*
 * bmlt-enabled/yap#1675: local passwords are stored with Hash::make() instead
 * of unsalted SHA-256. Legacy SHA-256 rows (including ones an operator inserts
 * by hand with SHA2(<password>, 256)) still log in, and are re-hashed on their
 * next successful login.
 */

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\FakeHttp;

beforeEach(function () {
    // A refused database login falls through to BMLT; never reach a real one.
    FakeHttp::install();
});

function pwInsertLegacy(string $username, string $password, string $authSource = User::AUTH_SOURCE_LOCAL): void
{
    DB::statement(
        "INSERT INTO users (name, username, password, permissions, is_admin, auth_source) VALUES ('Legacy', ?, SHA2(?, 256), 0, 1, ?)",
        [$username, $password, $authSource]
    );
}

function pwStored(string $username): string
{
    return (string) DB::table('users')->where('username', $username)->value('password');
}

function pwLogin(string $username, string $password)
{
    return test()->postJson('/api/v1/login', ['username' => $username, 'password' => $password]);
}

test('a legacy SHA-256 password logs in and is re-hashed', function () {
    pwInsertLegacy('legacy', 'legacy-pw');
    expect(pwStored('legacy'))->toBe(hash('sha256', 'legacy-pw'));

    pwLogin('legacy', 'legacy-pw')->assertStatus(200);

    $stored = pwStored('legacy');
    expect(User::isLegacyPasswordHash($stored))->toBeFalse();
    expect(Hash::info($stored)['algoName'])->toBe('bcrypt');
    expect(Hash::check('legacy-pw', $stored))->toBeTrue();

    // And the upgraded hash keeps working.
    pwLogin('legacy', 'legacy-pw')->assertStatus(200);
});

test('an upper-case legacy hash still logs in', function () {
    DB::table('users')->insert([
        'name' => 'Legacy', 'username' => 'upper', 'permissions' => 0, 'is_admin' => 0,
        'password' => strtoupper(hash('sha256', 'pw')),
    ]);

    pwLogin('upper', 'pw')->assertStatus(200);
    expect(User::isLegacyPasswordHash(pwStored('upper')))->toBeFalse();
});

test('a wrong password fails for a legacy hash and leaves it alone', function () {
    pwInsertLegacy('legacy', 'legacy-pw');

    pwLogin('legacy', 'wrong')->assertStatus(401);

    expect(pwStored('legacy'))->toBe(hash('sha256', 'legacy-pw'));
});

test('a user created through the API gets a salted hash and logs in', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $this->postJson('/api/v1/users', [
        'name' => 'New', 'username' => 'newuser', 'password' => 'new-pw',
        'permissions' => [], 'service_bodies' => [],
    ])->assertStatus(200);
    app('auth')->forgetGuards();

    $stored = pwStored('newuser');
    expect(User::isLegacyPasswordHash($stored))->toBeFalse();
    expect(Hash::check('new-pw', $stored))->toBeTrue();

    pwLogin('newuser', 'new-pw')->assertStatus(200);
});

test('a wrong password fails for a new-format hash', function () {
    User::saveUser('New', 'newuser', 'new-pw', [], []);
    $before = pwStored('newuser');

    pwLogin('newuser', 'wrong')->assertStatus(401);

    expect(pwStored('newuser'))->toBe($before);
});

test('two users with the same password get different hashes', function () {
    User::saveUser('One', 'one', 'same-pw', [], []);
    User::saveUser('Two', 'two', 'same-pw', [], []);

    expect(pwStored('one'))->not->toBe(pwStored('two'));
});

test('changing a password stores a salted hash', function () {
    pwInsertLegacy('legacy', 'legacy-pw');

    User::editUserForSelf('Legacy', 'legacy', 'changed-pw');
    $stored = pwStored('legacy');
    expect(User::isLegacyPasswordHash($stored))->toBeFalse();
    expect(Hash::check('changed-pw', $stored))->toBeTrue();

    User::editUserForAdmin('Legacy', 'legacy', 'admin-set-pw', [], []);
    expect(Hash::check('admin-set-pw', pwStored('legacy')))->toBeTrue();

    pwLogin('legacy', 'legacy-pw')->assertStatus(401);
    pwLogin('legacy', 'admin-set-pw')->assertStatus(200);
});

test('a BMLT row with an empty password never matches the database login', function () {
    User::syncBmltUser('bmltuser', [1]);
    expect(pwStored('bmltuser'))->toBe('');

    pwLogin('bmltuser', '')->assertStatus(422);
    pwLogin('bmltuser', 'anything')->assertStatus(401);

    $user = User::where('username', 'bmltuser')->first();
    expect($user->checkPassword(''))->toBeFalse();
});

test('a BMLT row that still carries a password hash is not checked locally', function () {
    pwInsertLegacy('bmltuser', 'pw', User::AUTH_SOURCE_BMLT);
    DB::table('users')->where('username', 'bmltuser')->update(['is_admin' => 0]);

    pwLogin('bmltuser', 'pw')->assertStatus(401);

    // Not re-hashed either: the database login never looked at it.
    expect(pwStored('bmltuser'))->toBe(hash('sha256', 'pw'));
});

test('a stored value that is neither format never matches', function () {
    DB::table('users')->insert([
        'name' => 'Odd', 'username' => 'odd', 'permissions' => 0, 'is_admin' => 0,
        'password' => 'not-a-hash',
    ]);

    pwLogin('odd', 'not-a-hash')->assertStatus(401);
});
