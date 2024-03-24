<?php

use App\Models\User;

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
        ->assertHeader("Content-Type", "text/html; charset=UTF-8");
});

test('login to authenticate with a BMLT user and a user with rights', function () {
    $response = $this->post(
        '/admin/login',
        ["username"=>"gnyr_admin","password"=>"CoreysGoryStory"]
    );

    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin/home')
        ->assertHeader("Content-Type", "text/html; charset=UTF-8");
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
        ->assertHeader("Content-Type", "text/html; charset=UTF-8");
});
