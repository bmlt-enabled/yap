<?php

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

test('language selector no languages set', function ($method) {
    $response = $this->call($method, '/lng-selector.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say>',
            'language gateway options are not set, please refer to the documentation to utilize this feature.',
            '</Say><Hangup/></Response>'
        ], false);
})->with(['GET', 'POST']);

test('language selector with languages set', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set("language_selections", "en-US,es-US");
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call($method, '/lng-selector.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Pause length="2"/>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="index.php" method="GET">',
            '<Say voice="alice" language="en-US">',
            'for english press one',
            '</Say>',
            '<Say voice="alice" language="es-US">',
            'para español presione dos',
            '</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
