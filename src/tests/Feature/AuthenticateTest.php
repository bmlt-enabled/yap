<?php

use App\Models\ConfigData;
use App\Models\User;
use App\Structures\Settings;
use Illuminate\Support\Facades\DB;
use Tests\FakeHttp;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    // The login endpoint falls through to the BMLT root server, so these tests
    // used to POST real credentials at it on every run.
    FakeHttp::install();
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
                INSERT INTO users (name, username, password, permissions, is_admin)
                VALUES ('admin', ?, SHA2(?, 256), 0, 1);
            ", [$username, $password]);

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

test('login seeds override settings for auth v2 service body admin', function () {
    $serviceBodyId = 1;
    $distinctiveTitle = 'override-title-1579-v2';

    $config = new Settings();
    $config->title = $distinctiveTitle;
    ConfigData::createServiceBodyConfiguration($serviceBodyId, $config);

    $username = 'sb_admin_v2';
    $password = 'testpass';
    User::saveUser('SB Admin', $username, $password, [], [(string) $serviceBodyId]);

    $result = $this->post('/api/v1/login', [
        'username' => $username,
        'password' => $password,
    ]);

    $result->assertStatus(200)
        ->assertSessionHas('override_title', $distinctiveTitle);
});

test('login seeds override settings for auth v1 bmlt user', function () {
    $serviceBodyId = 1;
    $distinctiveTitle = 'override-title-1579-v1';

    $config = new Settings();
    $config->title = $distinctiveTitle;
    ConfigData::createServiceBodyConfiguration($serviceBodyId, $config);

    $result = $this->post('/api/v1/login', [
        'username' => 'gnyr_admin',
        'password' => 'CoreysGoryStory',
    ]);

    $result->assertStatus(200)
        ->assertSessionHas('override_title', $distinctiveTitle);
});

test('login for auth v2 user with no service body rights does not error', function () {
    $username = 'no_rights_user';
    $password = 'testpass';
    User::saveUser('No Rights', $username, $password, [], []);

    $result = $this->post('/api/v1/login', [
        'username' => $username,
        'password' => $password,
    ]);

    $result->assertStatus(200);
    expect(session()->has('override_title'))->toBeFalse();
});
