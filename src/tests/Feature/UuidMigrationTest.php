<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const UUID_MIGRATION = '2025_01_01_163927_convert_id_to_guid_in_users_table';
const TOKENABLE_MIGRATION = '2025_01_01_174842_update_tokenable_id_to_uuid';

function rollbackUuidMigrations(): void
{
    if (Schema::hasTable('migrations_v2')) {
        $ran = DB::table('migrations_v2')->pluck('migration')->all();
        $steps = 0;
        if (in_array(TOKENABLE_MIGRATION, $ran, true)) {
            $steps++;
        }
        if (in_array(UUID_MIGRATION, $ran, true)) {
            $steps++;
        }
        if ($steps > 0) {
            Artisan::call('migrate:rollback', ['--step' => $steps, '--force' => true]);
        }
    }
}

function insertLegacyUser(
    int $id,
    string $username,
    string $name = 'Test User',
    string $password = 'hash'
): void {
    DB::table('users')->insert([
        'id' => $id,
        'name' => $name,
        'username' => $username,
        'password' => $password,
        'permissions' => 0,
        'is_admin' => 0,
        'created_on' => now(),
        'service_bodies' => null,
    ]);
}

function usersCreateTableSql(): string
{
    return DB::selectOne('SHOW CREATE TABLE users')->{'Create Table'};
}

beforeEach(function () {
    rollbackUuidMigrations();
    Schema::dropIfExists('users_pre_uuid_backup');
});

function runUuidMigration(): void
{
    require_once database_path('migrations/' . UUID_MIGRATION . '.php');
    $migration = new ConvertIdToGuidInUsersTable();
    $migration->up();
}

test('uuid migration preserves populated users with valid distinct uuids and primary key', function () {
    insertLegacyUser(1, 'alice');
    insertLegacyUser(2, 'bob');
    insertLegacyUser(3, 'unicode_ユーザー');
    insertLegacyUser(4, str_repeat('x', 45));

    $countBefore = DB::table('users')->count();

    runUuidMigration();

    expect(DB::table('users')->count())->toBe($countBefore);

    $ids = DB::table('users')->pluck('id')->all();
    expect($ids)->toHaveCount($countBefore);
    expect($ids)->each->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');

    expect(collect($ids)->unique()->count())->toBe($countBefore);
    expect(usersCreateTableSql())->toMatch('/PRIMARY KEY\s*\(`id`\)/');
    expect(Schema::hasTable('users_pre_uuid_backup'))->toBeTrue();
});

test('uuid migration aborts on duplicate username without altering users table', function () {
    DB::statement('ALTER TABLE users DROP INDEX username_unique');
    insertLegacyUser(10, 'dupe');
    insertLegacyUser(11, 'dupe');

    $sqlBefore = usersCreateTableSql();

    expect(fn () => runUuidMigration())->toThrow(\RuntimeException::class);

    expect(usersCreateTableSql())->toBe($sqlBefore);
    expect(Schema::hasTable('users_pre_uuid_backup'))->toBeFalse();
    expect(DB::table('users')->count())->toBe(2);
});

test('uuid migration aborts on null username without altering users table', function () {
    insertLegacyUser(20, 'valid_user');
    DB::statement('ALTER TABLE users MODIFY username VARCHAR(45) NULL');
    DB::table('users')->insert([
        'id' => 21,
        'name' => 'Null Username',
        'username' => null,
        'password' => 'hash',
        'permissions' => 0,
        'is_admin' => 0,
        'created_on' => now(),
        'service_bodies' => null,
    ]);

    $sqlBefore = usersCreateTableSql();

    expect(fn () => runUuidMigration())->toThrow(\RuntimeException::class);

    expect(usersCreateTableSql())->toBe($sqlBefore);
    expect(Schema::hasTable('users_pre_uuid_backup'))->toBeFalse();
});

test('uuid migration down restores original integer ids from backup', function () {
    insertLegacyUser(30, 'restore_me');
    insertLegacyUser(31, 'restore_too');

    runUuidMigration();

    require_once database_path('migrations/' . UUID_MIGRATION . '.php');
    $migration = new ConvertIdToGuidInUsersTable();
    $migration->down();

    $users = DB::table('users')->orderBy('id')->get();
    expect($users->pluck('id')->map(fn ($id) => (int) $id)->all())->toBe([30, 31]);
    expect($users->pluck('username')->all())->toBe(['restore_me', 'restore_too']);
    expect(Schema::hasTable('users_pre_uuid_backup'))->toBeFalse();
    expect(usersCreateTableSql())->toMatch('/PRIMARY KEY\s*\(`id`\)/');
});

test('personal access tokens join after uuid migration', function () {
    insertLegacyUser(40, 'token_user');

    runUuidMigration();
    Artisan::call('migrate', [
        '--path' => 'database/migrations/' . TOKENABLE_MIGRATION . '.php',
        '--force' => true,
    ]);

    $userId = DB::table('users')->where('username', 'token_user')->value('id');

    DB::table('personal_access_tokens')->insert([
        'tokenable_type' => 'App\\Models\\User',
        'tokenable_id' => $userId,
        'name' => 'test-token',
        'token' => hash('sha256', 'test-token-value'),
        'abilities' => '["*"]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $joined = DB::table('personal_access_tokens')
        ->join('users', 'users.id', '=', 'personal_access_tokens.tokenable_id')
        ->where('users.username', 'token_user')
        ->count();

    expect($joined)->toBe(1);
});
