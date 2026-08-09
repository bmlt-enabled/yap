<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const LEGACY_SCHEMA_MIGRATION = '2026_08_08_210000_add_legacy_schema_columns';

const LEGACY_COLUMNS = [
    'records_events' => ['type', 'meta'],
    'records' => ['type'],
    'conference_participants' => ['role'],
    'alerts' => ['status'],
    'cache' => ['expiry'],
    'cache_records_conference_participants' => ['service_body_id'],
    'metrics' => ['service_body_id'],
    'users' => ['service_bodies'],
];

function rollbackLegacySchemaMigrationRecord(): void
{
    if (Schema::hasTable('migrations_v2')) {
        DB::table('migrations_v2')->where('migration', LEGACY_SCHEMA_MIGRATION)->delete();
    }
}

function dropLegacyColumns(): void
{
    foreach (LEGACY_COLUMNS as $table => $columns) {
        if (!Schema::hasTable($table)) {
            continue;
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
}

function runLegacySchemaMigration(): void
{
    Artisan::call('migrate', [
        '--path' => 'database/migrations/' . LEGACY_SCHEMA_MIGRATION . '.php',
        '--force' => true,
    ]);
}

test('legacy schema catch-up migration adds missing columns', function () {
    dropLegacyColumns();
    rollbackLegacySchemaMigrationRecord();

    foreach (LEGACY_COLUMNS as $table => $columns) {
        foreach ($columns as $column) {
            expect(Schema::hasColumn($table, $column))->toBeFalse("expected {$table}.{$column} to be absent before migration");
        }
    }

    runLegacySchemaMigration();

    foreach (LEGACY_COLUMNS as $table => $columns) {
        foreach ($columns as $column) {
            expect(Schema::hasColumn($table, $column))->toBeTrue("expected {$table}.{$column} after migration");
        }
    }
});

test('legacy schema catch-up migration is idempotent', function () {
    dropLegacyColumns();
    rollbackLegacySchemaMigrationRecord();

    runLegacySchemaMigration();
    runLegacySchemaMigration();

    foreach (LEGACY_COLUMNS as $table => $columns) {
        foreach ($columns as $column) {
            expect(Schema::hasColumn($table, $column))->toBeTrue();
        }
    }
});

test('legacy schema catch-up migration allows call event inserts with type column', function () {
    dropLegacyColumns();
    rollbackLegacySchemaMigrationRecord();

    expect(Schema::hasColumn('records_events', 'type'))->toBeFalse();

    runLegacySchemaMigration();

    DB::table('records_events')->insert([
        'callsid' => 'CA123',
        'event_time' => now(),
        'event_id' => 1,
        'service_body_id' => null,
        'meta' => null,
        'type' => 2,
    ]);

    expect(DB::table('records_events')->where('callsid', 'CA123')->value('type'))->toBe(2);
});

test('migration middleware auto-applies legacy schema catch-up on predated schema', function () {
    dropLegacyColumns();
    rollbackLegacySchemaMigrationRecord();

    expect(Schema::hasColumn('records_events', 'type'))->toBeFalse();
    expect(Schema::hasColumn('records', 'type'))->toBeFalse();

    $response = $this->get('/api/v1/version');

    $response->assertStatus(200);
    expect(Schema::hasColumn('records_events', 'type'))->toBeTrue();
    expect(Schema::hasColumn('records', 'type'))->toBeTrue();
});
