<?php

use App\Services\Preflight\PreflightService;
use App\Services\SettingsService;

test('preflight service fails when database is not configured', function () {
    $settings = mock(SettingsService::class);
    $settings->shouldReceive('minimalRequiredSettings')->andReturn(['twilio_auth_token']);
    $settings->shouldReceive('get')->with('mysql_hostname')->andReturn('');
    $settings->shouldReceive('get')->with('mysql_database')->andReturn('');
    $settings->shouldReceive('get')->with('twilio_auth_token')->andReturn('token');

    $result = (new PreflightService($settings))->run();

    expect($result['passed'])->toBeFalse();
    expect(collect($result['checks'])->firstWhere('id', 'database')['passed'])->toBeFalse();
});

test('preflight service warns when app env is not production', function () {
    config(['app.env' => 'staging']);

    $settings = mock(SettingsService::class);
    $settings->shouldReceive('minimalRequiredSettings')->andReturn(['twilio_auth_token']);
    $settings->shouldReceive('get')->with('twilio_auth_token')->andReturn('token');
    $settings->shouldReceive('get')->with('mysql_hostname')->andReturn('127.0.0.1');
    $settings->shouldReceive('get')->with('mysql_database')->andReturn('yap_test');

    $result = (new PreflightService($settings))->run();
    $appEnvCheck = collect($result['checks'])->firstWhere('id', 'app_env');

    expect($appEnvCheck['passed'])->toBeFalse();
    expect($appEnvCheck['blocking'])->toBeFalse();
});
