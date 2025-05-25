<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

test('test login for invalid credentials', function () {
    $username = 'testuser';
    $password = 'testpass';
    User::saveUser('Bro bro', $username, $password, [], []);

    $result = $this->post(
        '/api/v1/login',
        ["username"=>'nope',"password"=>$password]
    );
    $result->assertStatus(401)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson(["message"=>"Invalid credentials"]);
});

test('test login for yap user with valid credentials', function () {
    $username = 'testuser';
    $password = 'testpass';
    User::saveUser('Bro bro', $username, $password, [], []);

    $result = $this->post(
        '/api/v1/login',
        ["username" => $username, "password" => $password]
    );
    $result->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([]);

    $token = $result->json('token');

    $protectedResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/settings'); // Replace with your actual protected endpoint

    $protectedResponse->assertStatus(200);
});

test('test login for admin yap user with valid credentials', function () {
    $username = 'admin1';
    $password = 'admin1';
    DB::statement("
                INSERT INTO users (id, name, username, password, permissions, is_admin)
                VALUES (?, ?, ?, SHA2(?, 256), 0, 1);
            ", [Str::uuid()->toString(), 'admin', $username, $password]);

    $result = $this->post(
        '/api/v1/login',
        ["username" => $username, "password" => $password]
    );
    $result->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([]);

    $token = $result->json('token');

    $protectedResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/settings'); // Replace with your actual protected endpoint

    $protectedResponse->assertStatus(200);
    User::deleteUser($username);
});

test('test login for bmlt user with valid credentials', function () {
    $username = 'gnyr_admin';
    $password = 'CoreysGoryStory';

    $result = $this->post(
        '/api/v1/login',
        ["username" => $username, "password" => $password]
    );
    $result->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([]);

    $token = $result->json('token');

    $protectedResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/settings'); // Replace with your actual protected endpoint

    $protectedResponse->assertStatus(200);
});
