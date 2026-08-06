<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

const DB_UUID_MIGRATION = '2025_01_01_163927_convert_id_to_guid_in_users_table';

test('returns maintenance page when destructive migrations are pending', function () {
    DB::table('migrations_v2')->where('migration', DB_UUID_MIGRATION)->delete();

    $response = $this->get('/api/v1/version');

    $response
        ->assertStatus(503)
        ->assertSee('Database Upgrade Required')
        ->assertSee('php artisan migrate')
        ->assertSee(DB_UUID_MIGRATION);
});

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
