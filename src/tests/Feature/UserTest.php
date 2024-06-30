<?php

use App\Constants\AuthMechanism;
use App\Models\User;
use Illuminate\Testing\Assert;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('create user', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['auth_is_admin'] = true;
    $username = 'test';
    $password = 'test';
    $response = $this->call('POST', '/api/v1/users', [
        'name'=>'test',
        'username'=>$username,
        'password'=>$password,
        'permissions'=>[1],
        'service_bodies'=>[1059, 1060]
    ]);
    $response
        ->assertStatus(200);

    $user = User::getUser($username);

    Assert::assertTrue($user->count() == 1);
});

test('create user and then delete it', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['auth_is_admin'] = true;
    $username = 'test';
    $password = 'test';
    $response = $this->call('POST', '/api/v1/users', [
        'name'=>'test',
        'username'=>$username,
        'password'=>$password,
        'permissions'=>[1],
        'service_bodies'=>[1059, 1060]
    ]);
    $response
        ->assertStatus(200);

    $user = User::getUser($username);

    Assert::assertTrue($user->count() == 1);

    $response = $this->call('DELETE', sprintf('/api/v1/users/%s', $username));
    $response
        ->assertStatus(200)
        ->assertJson(['message'=>sprintf('User %s deleted successfully', $username)])
        ->assertHeader('Content-Type', 'application/json');

    $user = User::getUser($username);

    Assert::assertTrue($user->count() == 0);
});

test('delete user without permissions does not exist', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;

    $username = 'bruh';
    $response = $this->call('DELETE', sprintf('/api/v1/users/%s', $username));
    $response
        ->assertStatus(404)
        ->assertJson(['message'=>'Not found'])
        ->assertHeader('Content-Type', 'application/json');
});


test('delete user that does not exist', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['auth_is_admin'] = true;

    $username = 'bruh';

    $response = $this->call('DELETE', sprintf('/api/v1/users/%s', $username));
    $response
        ->assertStatus(404)
        ->assertJson(['message'=>'Not found'])
        ->assertHeader('Content-Type', 'application/json');
});
