<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separate BMLT-backed users rows from local yap accounts (bmlt-enabled/yap#1674).
 *
 * 5.0 betas created a users row for every BMLT (V1) login and stored
 * sha256(<BMLT password>) in it, so later logins matched that row in the
 * database and never asked BMLT again. Those rows are now tagged
 * auth_source = 'bmlt', carry no usable password, and are skipped by the
 * database login.
 *
 * The beta rows were never tagged, so they are recognised by the shape the
 * beta login gave them: name equal to username, not an admin, and no yap
 * permissions. A local account that happens to look the same loses its
 * password; an admin can delete and recreate it.
 *
 * "Equal" means byte-for-byte: the beta copied the username into name. The
 * columns' collation is case-insensitive, so a plain comparison would also
 * convert a local account named "Jane" with username "jane".
 *
 * Every Sanctum token is also revoked, so nobody keeps a token issued under
 * the old rules (including one issued for a local admin to a same-named BMLT
 * login). Everyone signs in again after the upgrade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::whenTableDoesntHaveColumn('users', 'auth_source', function (Blueprint $table) {
            $table->string('auth_source', 16)->default('local');
        });

        DB::table('users')
            ->where('auth_source', 'local')
            ->whereRaw('CAST(`name` AS BINARY) = CAST(`username` AS BINARY)')
            ->where(function ($query) {
                $query->whereNull('is_admin')->orWhere('is_admin', 0);
            })
            ->where('permissions', 0)
            ->update(['auth_source' => 'bmlt', 'password' => '']);

        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')->delete();
        }
    }

    public function down(): void
    {
        Schema::whenTableHasColumn('users', 'auth_source', function (Blueprint $table) {
            $table->dropColumn('auth_source');
        });
    }
};
