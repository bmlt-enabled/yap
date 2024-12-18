<?php

use App\Models\User;

test('test login for invalid credentials', function() {
    $username = 'testuser';
    $password = 'testpass';
    User::saveUser('Bro bro', $username, $password, [], []);

    $result = $this->post(
        '/api/v1/login',
        ["username"=>'nope',"password"=>$password]);
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
        ["username" => $username, "password" => $password]);
    $result->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([]);

    $token = $result->json('token');

    $protectedResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/settings'); // Replace with your actual protected endpoint

    $protectedResponse->assertStatus(200);
});
