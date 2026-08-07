<?php

use App\Http\Middleware\DatabaseMigrations;
use App\Services\SettingsService;
use Illuminate\Database\Migrations\MigrationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function makeDatabaseMigrationsMiddleware(
    SettingsService $settings,
    ?MigrationRepository $repository = null,
): DatabaseMigrations {
    return new DatabaseMigrations(
        $settings,
        $repository ?? mock(MigrationRepository::class),
    );
}

test('skips migrations when mysql hostname is empty', function () {
    Artisan::spy();

    $settings = mock(SettingsService::class);
    $settings->shouldReceive('get')->with('mysql_hostname')->andReturn('');
    $settings->shouldReceive('get')->with('mysql_database')->andReturn('yap');

    $middleware = makeDatabaseMigrationsMiddleware($settings);
    $called = false;

    $middleware->handle(Request::create('/'), function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
    Artisan::shouldNotHaveReceived('call');
});

test('skips migrations when mysql database is empty', function () {
    Artisan::spy();

    $settings = mock(SettingsService::class);
    $settings->shouldReceive('get')->with('mysql_hostname')->andReturn('127.0.0.1');
    $settings->shouldReceive('get')->with('mysql_database')->andReturn('');

    $middleware = makeDatabaseMigrationsMiddleware($settings);
    $called = false;

    $middleware->handle(Request::create('/'), function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
    Artisan::shouldNotHaveReceived('call');
});

test('skips migrations when schema is up to date', function () {
    Artisan::spy();
    Schema::shouldReceive('hasTable')->with('migrations_v2')->andReturn(true);

    $settings = mock(SettingsService::class);
    $settings->shouldReceive('get')->with('mysql_hostname')->andReturn('127.0.0.1');
    $settings->shouldReceive('get')->with('mysql_database')->andReturn('yap');

    $repository = mock(MigrationRepository::class);
    $repository->shouldReceive('getRan')->andReturn([
        '2026_01_03_000000_create_chat_sessions_table',
    ]);

    $middleware = makeDatabaseMigrationsMiddleware($settings, $repository);
    $called = false;

    $middleware->handle(Request::create('/'), function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
    Artisan::shouldNotHaveReceived('call');
});

test('runs migrations when configured and latest migration is missing', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', ['--force' => true]);
    Schema::shouldReceive('hasTable')->with('migrations_v2')->andReturn(true);
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

    $repository = mock(MigrationRepository::class);
    $repository->shouldReceive('getRan')->andReturn([
        '2024_03_24_035821_create_config_table',
    ]);

    $middleware = makeDatabaseMigrationsMiddleware($settings, $repository);
    $called = false;

    $middleware->handle(Request::create('/'), function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
});

test('runs migrations when configured and migrations table is missing', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', ['--force' => true]);
    Schema::shouldReceive('hasTable')->with('migrations_v2')->andReturn(false);
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

    $repository = mock(MigrationRepository::class);
    $repository->shouldNotReceive('getRan');

    $middleware = makeDatabaseMigrationsMiddleware($settings, $repository);
    $called = false;

    $middleware->handle(Request::create('/'), function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
});
