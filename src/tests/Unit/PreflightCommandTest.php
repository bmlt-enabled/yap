<?php

use App\Services\SettingsService;

test('preflight fails when twilio_auth_token is empty', function () {
    putenv('ENVIRONMENT=test');
    $settings = new SettingsService();
    $settings->set('twilio_auth_token', '');
    app()->instance(SettingsService::class, $settings);

    $this->artisan('yap:preflight')
        ->expectsOutputToContain('[FAIL] Twilio auth token')
        ->assertFailed();
});

test('preflight passes when twilio_auth_token is configured', function () {
    putenv('ENVIRONMENT=test');
    $settings = new SettingsService();
    app()->instance(SettingsService::class, $settings);

    $this->artisan('yap:preflight')
        ->expectsOutputToContain('[PASS] Twilio auth token')
        ->assertSuccessful();
});
