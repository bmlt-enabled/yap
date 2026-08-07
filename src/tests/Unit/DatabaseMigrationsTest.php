<?php

use App\Http\Middleware\DatabaseMigrations;
use App\Services\DatabaseMigrationService;
use App\Services\SettingsService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function makeDatabaseMigrationsMiddleware(
    SettingsService $settings,
    ?DatabaseMigrationService $migrationService = null,
): DatabaseMigrations {
    $migrationService ??= mock(DatabaseMigrationService::class);
    $migrationService->shouldReceive('pendingDestructiveMigrationNames')->andReturn([]);

    return new DatabaseMigrations($settings, $migrationService);
}

function mockLatestMigrationExists(bool $exists): void
{
    $query = mock(Builder::class);
    $query->shouldReceive('where')->with('migration', '2026_01_03_000000_create_chat_sessions_table')->andReturnSelf();
    $query->shouldReceive('exists')->andReturn($exists);

    DB::shouldReceive('table')->with('migrations_v2')->andReturn($query);
}

test('skips migrations when mysql hostname is empty', function () {
    $settings = mock(SettingsService::class);
    $settings->shouldReceive('get')->with('mysql_hostname')->andReturn('');
    $settings->shouldReceive('get')->with('mysql_database')->andReturn('yap');

    $migrationService = mock(DatabaseMigrationService::class);
    $migrationService->shouldNotReceive('pendingDestructiveMigrationNames');
    $migrationService->shouldNotReceive('runSafePendingMigrations');

    $middleware = new DatabaseMigrations($settings, $migrationService);
    $called = false;

    $middleware->handle(Request::create('/'), function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
});

test('skips migrations when mysql database is empty', function () {
    $settings = mock(SettingsService::class);
    $settings->shouldReceive('get')->with('mysql_hostname')->andReturn('127.0.0.1');
    $settings->shouldReceive('get')->with('mysql_database')->andReturn('');

    $migrationService = mock(DatabaseMigrationService::class);
    $migrationService->shouldNotReceive('pendingDestructiveMigrationNames');
    $migrationService->shouldNotReceive('runSafePendingMigrations');

    $middleware = new DatabaseMigrations($settings, $migrationService);
    $called = false;

    $middleware->handle(Request::create('/'), function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
});

test('skips migrations when schema is up to date', function () {
    Schema::shouldReceive('hasTable')->with('migrations_v2')->andReturn(true);
    mockLatestMigrationExists(true);

    $settings = mock(SettingsService::class);
    $settings->shouldReceive('get')->with('mysql_hostname')->andReturn('127.0.0.1');
    $settings->shouldReceive('get')->with('mysql_database')->andReturn('yap');

    $migrationService = mock(DatabaseMigrationService::class);
    $migrationService->shouldReceive('pendingDestructiveMigrationNames')->once()->andReturn([]);
    $migrationService->shouldNotReceive('runSafePendingMigrations');

    $middleware = new DatabaseMigrations($settings, $migrationService);
    $called = false;

    $middleware->handle(Request::create('/'), function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
});

test('runs migrations when configured and latest migration is missing', function () {
    Schema::shouldReceive('hasTable')->with('migrations_v2')->andReturn(true);
    mockLatestMigrationExists(false);
    DB::partialMock()
        ->shouldReceive('select')
        ->once()
        ->with('SELECT GET_LOCK(?, 600)', ['yap-db-migrations'])
        ->andReturn([(object) ['GET_LOCK(?)' => 1]])
        ->shouldReceive('statement')
        ->once()
        ->with('SELECT RELEASE_LOCK(?)', ['yap-db-migrations']);

    $settings = mock(SettingsService::class);
    $settings->shouldReceive('get')->with('mysql_hostname')->andReturn('127.0.0.1');
    $settings->shouldReceive('get')->with('mysql_database')->andReturn('yap');

    $migrationService = mock(DatabaseMigrationService::class);
    $migrationService->shouldReceive('pendingDestructiveMigrationNames')->once()->andReturn([]);
    $migrationService->shouldReceive('runSafePendingMigrations')->once();

    $middleware = new DatabaseMigrations($settings, $migrationService);
    $called = false;

    $middleware->handle(Request::create('/'), function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
});

test('runs migrations when configured and migrations table is missing', function () {
    Schema::shouldReceive('hasTable')->with('migrations_v2')->andReturn(false);
    DB::partialMock()
        ->shouldReceive('select')
        ->once()
        ->with('SELECT GET_LOCK(?, 600)', ['yap-db-migrations'])
        ->andReturn([(object) ['GET_LOCK(?)' => 1]])
        ->shouldReceive('statement')
        ->once()
        ->with('SELECT RELEASE_LOCK(?)', ['yap-db-migrations']);
    DB::shouldNotReceive('table');

    $settings = mock(SettingsService::class);
    $settings->shouldReceive('get')->with('mysql_hostname')->andReturn('127.0.0.1');
    $settings->shouldReceive('get')->with('mysql_database')->andReturn('yap');

    $migrationService = mock(DatabaseMigrationService::class);
    $migrationService->shouldReceive('pendingDestructiveMigrationNames')->once()->andReturn([]);
    $migrationService->shouldReceive('runSafePendingMigrations')->once();

    $middleware = new DatabaseMigrations($settings, $migrationService);
    $called = false;

    $middleware->handle(Request::create('/'), function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
});

test('returns maintenance page when destructive migrations are pending', function () {
    $settings = mock(SettingsService::class);
    $settings->shouldReceive('get')->with('mysql_hostname')->andReturn('127.0.0.1');
    $settings->shouldReceive('get')->with('mysql_database')->andReturn('yap');

    $migrationService = mock(DatabaseMigrationService::class);
    $migrationService->shouldReceive('pendingDestructiveMigrationNames')->once()->andReturn([
        '2025_01_01_163927_convert_id_to_guid_in_users_table',
    ]);
    $migrationService->shouldNotReceive('runSafePendingMigrations');

    $middleware = new DatabaseMigrations($settings, $migrationService);
    $called = false;

    $response = $middleware->handle(Request::create('/'), function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeFalse();
    expect($response->getStatusCode())->toBe(503);
    expect($response->getContent())->toContain('Database Upgrade Required');
});
