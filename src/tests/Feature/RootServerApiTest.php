<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
});

test('get service bodies', function () {
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

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/rootServer/serviceBodies'); // Replace with your actual protected endpoint
    $response
        ->assertStatus(200)
        ->assertJson(function ($json) {
            return is_array($json) && !empty($json);
        });
});

test('get service bodies for admin user', function () {
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

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/rootServer/serviceBodies'); // Replace with your actual protected endpoint
    $response
        ->assertStatus(200)
        ->assertJson(function ($json) {
            return is_array($json) && !empty($json);
        });
});

test('get service bodies no auth', function () {
    $response = $this->call('GET', '/api/v1/rootServer/serviceBodies');
    $response
        ->assertHeader("Location", "http://localhost/api/v1/login")
        ->assertHeader("Content-Type", "text/html; charset=utf-8")
        ->assertStatus(302);
});
