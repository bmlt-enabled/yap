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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->first();
        });

        // Populate the new UUID column with unique GUIDs
        DB::table('users')->get()->each(function ($user) {
            DB::table('users')->where('username', $user->username)->update([
                'id' => Str::uuid()->toString(),
            ]);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('id')->primary()->first();
        });

        // Repopulate the `old_id` column (example logic; adapt as needed)
        DB::table('users')->get()->each(function ($user) {
            DB::table('users')->where('username', $user->username)->update([
                'id' => rand(1, 100000), // Example: Generate dummy integer IDs
            ]);
        });
    }
}

