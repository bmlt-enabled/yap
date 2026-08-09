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

test('preflight service reports php extensions check', function () {
    $settings = mock(SettingsService::class);
    $settings->shouldReceive('minimalRequiredSettings')->andReturn(['twilio_auth_token']);
    $settings->shouldReceive('get')->with('twilio_auth_token')->andReturn('token');
    $settings->shouldReceive('get')->with('mysql_hostname')->andReturn('');
    $settings->shouldReceive('get')->with('mysql_database')->andReturn('');

    $result = (new PreflightService($settings))->run();
    $extensionCheck = collect($result['checks'])->firstWhere('id', 'php_extensions');

    expect($extensionCheck)->not->toBeNull();
    expect($extensionCheck['label'])->toBe('PHP extensions');
    expect($extensionCheck['passed'])->toBeTrue();
});

test('php extension requirements include fileinfo and pdo_mysql', function () {
    expect(\App\Services\Preflight\PhpExtensionRequirements::REQUIRED)
        ->toHaveKey('fileinfo')
        ->toHaveKey('pdo_mysql');
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
