<?php

use App\Constants\AuthMechanism;
use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerRoutingType;
use App\Models\ConfigData;
use App\Models\ServiceBodyCallHandling;
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
