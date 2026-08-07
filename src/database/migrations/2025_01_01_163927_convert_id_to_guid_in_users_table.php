<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ConvertIdToGuidInUsersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('users_pre_uuid_backup')) {
            throw new \RuntimeException(
                'users_pre_uuid_backup already exists. A prior migration attempt may have failed. ' .
                'Inspect the backup table and drop it manually before retrying.'
            );
        }

        $this->preflight();

        $rowCountBefore = DB::table('users')->count();

        DB::statement('CREATE TABLE users_pre_uuid_backup AS SELECT * FROM users');

        $idMapping = [];
        foreach (DB::table('users')->select('id')->get() as $user) {
            $idMapping[$user->id] = Str::uuid()->toString();
        }

        Schema::table('users', function (Blueprint $table) {
            $table->integer('migration_old_id')->nullable();
        });
        DB::statement('UPDATE users SET migration_old_id = id');

        DB::statement('ALTER TABLE users MODIFY id INT NOT NULL');
        DB::statement('ALTER TABLE users DROP PRIMARY KEY');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->nullable()->first();
        });

        foreach ($idMapping as $oldId => $newUuid) {
            DB::table('users')
                ->where('migration_old_id', $oldId)
                ->update(['id' => $newUuid]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('migration_old_id');
        });

        DB::statement('ALTER TABLE users MODIFY id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE users ADD PRIMARY KEY (id)');

        $this->postAssert($rowCountBefore);
    }

    public function down()
    {
        if (!Schema::hasTable('users_pre_uuid_backup')) {
            throw new \RuntimeException(
                'Cannot reverse UUID migration: users_pre_uuid_backup table not found. ' .
                'Restore from your own database backup.'
            );
        }

        Schema::dropIfExists('users');
        DB::statement('CREATE TABLE users LIKE users_pre_uuid_backup');
        DB::statement('INSERT INTO users SELECT * FROM users_pre_uuid_backup');

        // CREATE TABLE ... AS SELECT does not copy indexes onto the backup table,
        // so LIKE/INSERT cannot restore them; re-apply the original constraints.
        DB::statement('ALTER TABLE users MODIFY id INT NOT NULL');
        DB::statement('ALTER TABLE users ADD PRIMARY KEY (id)');
        DB::statement('ALTER TABLE users MODIFY id INT NOT NULL AUTO_INCREMENT');
        DB::statement('ALTER TABLE users ADD UNIQUE INDEX username_unique (username)');

        Schema::dropIfExists('users_pre_uuid_backup');
    }

    private function preflight(): void
    {
        $nullOrEmptyUsername = DB::table('users')
            ->where(function ($query) {
                $query->whereNull('username')->orWhere('username', '');
            })
            ->count();

        if ($nullOrEmptyUsername > 0) {
            throw new \RuntimeException(
                "UUID migration aborted: {$nullOrEmptyUsername} user(s) have NULL or empty username. " .
                'Fix these rows before migrating.'
            );
        }

        $duplicateUsernames = DB::select(
            'SELECT username, COUNT(*) as cnt FROM users GROUP BY username HAVING cnt > 1'
        );

        if (count($duplicateUsernames) > 0) {
            $examples = collect($duplicateUsernames)->pluck('username')->take(5)->implode(', ');
            throw new \RuntimeException(
                "UUID migration aborted: duplicate username(s) found ({$examples}). " .
                'Resolve duplicates before migrating.'
            );
        }
    }

    private function postAssert(int $expectedRowCount): void
    {
        $actualCount = DB::table('users')->count();
        if ($actualCount !== $expectedRowCount) {
            throw new \RuntimeException(
                "UUID migration post-check failed: expected {$expectedRowCount} rows, found {$actualCount}."
            );
        }

        $nullIds = DB::table('users')->whereNull('id')->count();
        if ($nullIds > 0) {
            throw new \RuntimeException(
                "UUID migration post-check failed: {$nullIds} user(s) have NULL id."
            );
        }

        $duplicateIds = DB::selectOne(
            'SELECT COUNT(*) - COUNT(DISTINCT id) as dupes FROM users'
        )->dupes;
        if ($duplicateIds > 0) {
            throw new \RuntimeException(
                'UUID migration post-check failed: duplicate user ids found.'
            );
        }

        $createTable = DB::selectOne('SHOW CREATE TABLE users');
        $sql = $createTable->{'Create Table'};
        if (!preg_match('/PRIMARY KEY\s*\(`id`\)/', $sql)) {
            throw new \RuntimeException(
                'UUID migration post-check failed: users.id primary key is not present.'
            );
        }
    }
}
