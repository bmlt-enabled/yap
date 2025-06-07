<?php

use App\Services\SettingsService;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
});

test('get a SPAD in English', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set("word_language", "en-US");
    app()->instance(SettingsService::class, $settingsService);

    $response = $this->call($method, '/fetch-spad.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeText("All Rights Reserved", false);
})->with(['GET', 'POST']);

