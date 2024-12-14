<?php

use App\Models\User;

test('test login for invalid credentials', function() {

});

test('test login for yap user with valid credentials', function () {
    $username = 'testuser';
    $password = 'testpass';
    $this->withoutExceptionHandling();
    $user = User::saveUser('Bro bro', $username, $password, [], []);

    $result = $this->post(
        '/api/v1/login',
        ["username"=>$username,"password"=>$password]);
    $result->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([]);

    $token = $result->json('token'); // Extract the token

    $protectedResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/settings'); // Replace with your actual protected endpoint

    $protectedResponse->assertStatus(200);
});
