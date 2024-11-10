<?php

use App\Constants\AuthMechanism;
use App\Constants\VolunteerRoutingType;
use App\Models\ConfigData;
use App\Models\User;
use App\Structures\ServiceBodyCallHandling;
use Illuminate\Foundation\Testing\RefreshDatabase;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('login to authenticate with a BMLT user and a user with no rights', function () {
    $this->post(
        '/admin/login',
        ["username"=>"yap","password"=>"CoreysGoryStory"]
    )->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin/auth/invalid')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");
});

test('login to authenticate with a BMLT user and a user with rights', function () {
    $response = $this->post(
        '/admin/login',
        ["username"=>"gnyr_admin","password"=>"CoreysGoryStory"]
    );

    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin/home')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");
});

test('login to authenticate with a yap admin user', function () {
    User::create([
        "name"=>"admin",
        "username"=>"admin",
        "password"=>hash("sha256", "admin"),
        "permissions"=>0,
        "is_admin"=>1
    ]);

    $response = $this->post(
        '/admin/login',
        ["username"=>"admin","password"=>"admin"]
    );
    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin/home')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");
});

test('login to authenticate with a yap user', function () {
    User::create([
        "name"=>"user",
        "username"=>"user",
        "password"=>hash("sha256", "user"),
        "permissions"=>0,
        "is_admin"=>0,
        "service_bodies"=>"1006,1005"
    ]);

    $response = $this->post(
        '/admin/login',
        ["username"=>"user","password"=>"user"]
    );
    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin/home')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");
});

test('logout with a yap admin user', function () {
    User::create([
        "name"=>"admin",
        "username"=>"admin",
        "password"=>hash("sha256", "admin"),
        "permissions"=>0,
        "is_admin"=>1
    ]);

    $response = $this->get(
        '/admin/auth/logout'
    );
    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");
});

test('logout with a BMLT user', function () {
    $this->withoutExceptionHandling();
    $_SESSION['auth_mechanism'] = AuthMechanism::V1;
    $_SESSION['bmlt_auth_session'] = ["test_cookie=blah"];
    $response = $this->get(
        '/admin/auth/logout'
    );
    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");
});

// TODO: this test should be removed after we load data solely with React
test('get service body call handling (legacy)', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['auth_is_admin'] = true;
    $_SESSION['username'] = "admin";

    $serviceBodyCallHandling = new ServiceBodyCallHandling();
    $serviceBodyCallHandling->volunteer_routing_enabled = true;
    $serviceBodyCallHandling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;

    ConfigData::createServiceBodyCallHandling(1, $serviceBodyCallHandling);

    $response = $this->get('/admin/volunteers');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/html; charset=UTF-8");
});
