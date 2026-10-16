<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel's database session store. Yap's "sessions" table holds call PINs, so
 * the database session driver gets its own table, selected with
 * SESSION_TABLE=laravel_sessions. Installs that keep SESSION_DRIVER=file leave
 * this table empty. Used by the Render Blueprint, whose filesystem is ephemeral.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('laravel_sessions')) {
            return;
        }

        Schema::create('laravel_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id', 36)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laravel_sessions');
    }
};
