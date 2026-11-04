<?php

/*
 * Regression tests for bmlt-enabled/yap#1674: the 5.0 admin login mixed the
 * Sanctum bearer token with the Laravel session when deciding permissions, and
 * mapped BMLT (V1) logins onto `users` rows in a way that let them skip BMLT
 * or take over a local account. Each test below is one repro from that issue.
 *
 * Authorization is now driven entirely by the token's user, so most of these
 * tests deliberately make "token only" requests: no session data and no user
 * cached on a guard from an earlier request in the same test.
 */

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\FakeHttp;

const BMLT_USER = 'gnyr_admin';
const BMLT_PASSWORD = 'bmlt-password';

/**
 * Fake BMLT with a login endpoint and permissions response the test can change
 * between requests. FakeHttp keeps the first stub that matches, so these have
 * to be registered in the same install() call rather than layered on later.
 */
function fakeBmlt(object $bmlt): void
{
    FakeHttp::install([
        '*/local_server/server_admin/xml.php*' => function (Request $request) use ($bmlt) {
            $data = $request->data();
            $ok = ($data['c_comdef_admin_login'] ?? null) === BMLT_USER
                && ($data['c_comdef_admin_password'] ?? null) === $bmlt->password;
            return Http::response($ok ? FakeHttp::fixture('bmlt/auth-ok.txt') : 'NOT AUTHORIZED');
        },
        '*/server_admin/json.php*get_permissions*' => function () use ($bmlt) {
            return Http::response(json_encode(['service_body' => array_map(
                fn ($id) => ['id' => $id, 'name' => "sb $id", 'permissions' => 3],
                $bmlt->serviceBodies
            )]));
        },
    ]);
}

function bmltLoginCalls(): int
{
    return count(Http::recorded(function (Request $request) {
        return str_contains($request->url(), '/server_admin/xml.php')
            && ($request->data()['admin_action'] ?? null) === 'login';
    }));
}

function createLocalAdmin(string $username, string $password): void
{
    DB::statement(
        "INSERT INTO users (name, username, password, permissions, is_admin) VALUES ('Local Admin', ?, SHA2(?, 256), 0, 1)",
        [$username, $password]
    );
}

/**
 * Forget the user every guard resolved during earlier requests in this test,
 * so the next request authenticates from scratch like a real one would.
 */
function freshGuards(): void
{
    app('auth')->forgetGuards();
}

/**
 * Drop the session as well, leaving the bearer token as the only credential.
 */
function tokenOnly(): void
{
    session()->flush();
    freshGuards();
}

beforeEach(function () {
    $this->bmlt = (object) ['password' => BMLT_PASSWORD, 'serviceBodies' => [1]];
    fakeBmlt($this->bmlt);
});

// --- 1. Username clash: a BMLT login must not get a local admin's token ------

test('a BMLT login that shares a local admin username is refused', function () {
    createLocalAdmin(BMLT_USER, 'local-password');

    $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])
        ->assertStatus(401);

    expect(DB::table('personal_access_tokens')->count())->toBe(0);
    expect(User::where('username', BMLT_USER)->value('is_admin'))->toBe(1);
});

test('the local admin who shares a BMLT username still logs in with the local password', function () {
    createLocalAdmin(BMLT_USER, 'local-password');

    $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => 'local-password'])
        ->assertStatus(200)
        ->assertJsonPath('user.is_admin', 1);

    expect(bmltLoginCalls())->toBe(0);
});

test('a BMLT user token cannot manage users', function () {
    $token = $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])
        ->assertStatus(200)
        ->json('token');

    tokenOnly();
    $this->withToken($token)->getJson('/api/v1/users')->assertStatus(200)->assertExactJson([]);

    freshGuards();
    $this->withToken($token)->postJson('/api/v1/users', [
        'name' => 'x', 'username' => 'x', 'password' => 'x', 'permissions' => [], 'service_bodies' => [],
    ])->assertStatus(404);
});

// --- 2. Every BMLT login is checked against BMLT -----------------------------

test('every BMLT login checks the password with BMLT', function () {
    $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])->assertStatus(200);
    $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])->assertStatus(200);

    expect(bmltLoginCalls())->toBe(2);
});

test('a BMLT user row never stores a usable password', function () {
    $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])->assertStatus(200);

    $row = DB::table('users')->where('username', BMLT_USER)->first();
    expect($row->password)->not->toBe(hash('sha256', BMLT_PASSWORD));
    expect($row->auth_source)->toBe(User::AUTH_SOURCE_BMLT);
});

test('changing the BMLT password locks the old password out of yap', function () {
    $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])->assertStatus(200);

    $this->bmlt->password = 'new-bmlt-password';

    $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])->assertStatus(401);
    $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => 'new-bmlt-password'])->assertStatus(200);
});

test('BMLT service body rights are refreshed on every login', function () {
    $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])->assertStatus(200);

    $this->bmlt->serviceBodies = [1006];
    $token = $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])
        ->assertStatus(200)
        ->json('token');

    expect(User::where('username', BMLT_USER)->value('service_bodies'))->toBe('1006');

    tokenOnly();
    $ids = collect($this->withToken($token)->getJson('/api/v1/rootServer/serviceBodies/user')->assertStatus(200)->json())
        ->pluck('id')->map(fn ($id) => intval($id))->all();
    expect($ids)->toBe([1006]);
});

test('a BMLT user who loses every service body right can no longer log in', function () {
    $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])->assertStatus(200);

    $this->bmlt->serviceBodies = [];

    $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])->assertStatus(401);
});

// --- 3. A leftover session grants nothing -----------------------------------

test('a BMLT login in a session left over from a local admin gets no admin rights', function () {
    createLocalAdmin('admin1', 'admin1');
    $this->postJson('/api/v1/login', ['username' => 'admin1', 'password' => 'admin1'])->assertStatus(200);

    // Same browser session, no logout in between.
    freshGuards();
    $token = $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])
        ->assertStatus(200)
        ->json('token');

    expect(session('auth_is_admin'))->toBeEmpty();

    // Token plus the leftover session cookie.
    freshGuards();
    $this->withToken($token)->getJson('/api/v1/users')->assertStatus(200)->assertExactJson([]);
});

test('login does not keep auth keys from an earlier session', function () {
    session()->put([
        'auth_is_admin' => 1,
        'auth_permissions' => 255,
        'auth_service_bodies' => ['1', '2'],
        'auth_service_bodies_rights' => [1, 2],
    ]);

    $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])->assertStatus(200);

    foreach (['auth_is_admin', 'auth_permissions', 'auth_service_bodies', 'auth_service_bodies_rights'] as $key) {
        expect(session()->has($key))->toBeFalse("session still has $key");
    }
});

test('admin keys planted in the session do not elevate a token', function () {
    $user = User::factory()->create(['password' => hash('sha256', 'pw')]);
    $token = $this->postJson('/api/v1/login', ['username' => $user->username, 'password' => 'pw'])
        ->assertStatus(200)
        ->json('token');

    freshGuards();
    session()->put(['auth_is_admin' => 1, 'auth_permissions' => 255]);

    $this->withToken($token)->getJson('/api/v1/users')->assertStatus(200)->assertExactJson([]);
});

// --- 4. The password hash never leaves the server ----------------------------

test('login and /api/v1/user do not return the password for a local user', function () {
    createLocalAdmin('admin1', 'admin1');

    $login = $this->postJson('/api/v1/login', ['username' => 'admin1', 'password' => 'admin1'])->assertStatus(200);
    expect($login->json('user'))->not->toHaveKey('password');

    tokenOnly();
    $me = $this->withToken($login->json('token'))->getJson('/api/v1/user')->assertStatus(200);
    expect($me->json())->toHaveKey('username')->not->toHaveKey('password');
});

test('login and /api/v1/user do not return the password for a BMLT user', function () {
    $login = $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])->assertStatus(200);
    expect($login->json('user'))->not->toHaveKey('password');

    tokenOnly();
    $me = $this->withToken($login->json('token'))->getJson('/api/v1/user')->assertStatus(200);
    expect($me->json())->toHaveKey('username')->not->toHaveKey('password');
});

// --- 5. The token alone carries the user's permissions -----------------------

test('a local user token keeps its service body rights without the session', function () {
    $user = User::factory()->create(['password' => hash('sha256', 'pw'), 'service_bodies' => '1']);
    $token = $this->postJson('/api/v1/login', ['username' => $user->username, 'password' => 'pw'])
        ->assertStatus(200)
        ->json('token');

    tokenOnly();
    $this->withToken($token)->getJson('/api/v1/volunteers?serviceBodyId=1')->assertStatus(200);

    freshGuards();
    $this->withToken($token)->getJson('/api/v1/volunteers?serviceBodyId=2')->assertStatus(403);
});

test('a BMLT user token keeps its service body rights without the session', function () {
    $token = $this->postJson('/api/v1/login', ['username' => BMLT_USER, 'password' => BMLT_PASSWORD])
        ->assertStatus(200)
        ->json('token');

    tokenOnly();
    $this->withToken($token)->getJson('/api/v1/volunteers?serviceBodyId=1')->assertStatus(200);

    freshGuards();
    $this->withToken($token)->getJson('/api/v1/volunteers?serviceBodyId=2')->assertStatus(403);
});

test('a session without a token is not authenticated', function () {
    createLocalAdmin('admin1', 'admin1');
    $this->postJson('/api/v1/login', ['username' => 'admin1', 'password' => 'admin1'])->assertStatus(200);

    freshGuards();
    $this->getJson('/api/v1/user')->assertStatus(401);
});
