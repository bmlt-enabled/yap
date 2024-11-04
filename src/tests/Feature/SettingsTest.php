<?php

use App\Constants\AuthMechanism;
use App\Services\SettingsService;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('check language selections when not set', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $response = $this->call('GET', '/api/v1/settings');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson(["languageSelections"=>[""]]);
});

test('check language selections when set', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $settingsService = new SettingsService();
    $settingsService->set('language_selections', "en-US,fr-CA");
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call('GET', '/api/v1/settings');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson(["languageSelections"=>["en-US", "fr-CA"]]);
});

test('check language selections tagged when set', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $settingsService = new SettingsService();
    $settingsService->set('language_selections_tagging', "en-US,fr-CA");
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call('GET', '/api/v1/settings');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson(["languageSelections"=>["en-US", "fr-CA"]]);
});
