<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

test('allows requests when all migrations have been applied', function () {
    Artisan::call('migrate', ['--force' => true]);

    $response = $this->get('/api/v1/version');

    $response->assertStatus(200);
});

test('config check blocks migration middleware when config is missing', function () {
    $settings = app(\App\Services\SettingsService::class);
    $configPath = $settings->getConfigFilenameForEnvironment();

    if (file_exists($configPath)) {
        rename($configPath, $configPath . '.bak');
    }

    try {
        $response = $this->get('/api/v1/version');

        $response
            ->assertStatus(200)
            ->assertSee('Yap Installer');
    } finally {
        if (file_exists($configPath . '.bak')) {
            rename($configPath . '.bak', $configPath);
        }
    }
});
