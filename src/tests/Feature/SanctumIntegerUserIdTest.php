<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\FakeHttp;

beforeEach(function () {
    FakeHttp::install();
});

test('users keep integer auto-increment primary keys', function () {
    $user = User::factory()->create(['username' => 'integer_pk_user']);

    expect($user->id)->toBeInt();
    expect(DB::table('users')->where('username', 'integer_pk_user')->value('id'))->toBeInt();
});

test('sanctum personal access tokens join on integer user id', function () {
    $user = User::factory()->create(['username' => 'token_user']);

    $token = $user->createToken('test-token');

    $joined = DB::table('personal_access_tokens')
        ->join('users', 'users.id', '=', 'personal_access_tokens.tokenable_id')
        ->where('users.username', 'token_user')
        ->where('personal_access_tokens.id', $token->accessToken->id)
        ->count();

    expect($joined)->toBe(1);
});

test('login issues a sanctum token for integer-keyed users', function () {
    User::saveUser('Token Test', 'token_login', 'secret', [], []);

    $response = $this->postJson('/api/v1/login', [
        'username' => 'token_login',
        'password' => 'secret',
    ]);

    $response->assertOk()->assertJsonStructure(['token']);

    $this->withToken($response->json('token'))
        ->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('username', 'token_login');
});
