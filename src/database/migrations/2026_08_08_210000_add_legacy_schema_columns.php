<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add columns that pre-Laravel installs may be missing. Laravel create
     * migrations skip ALTER when the table already exists, so long-running
     * databases can lack columns that fresh installs get from CREATE TABLE.
     */
    public function up(): void
    {
        Schema::whenTableDoesntHaveColumn('records_events', 'type', function (Blueprint $table) {
            $table->addColumn('integer', 'type')->nullable();
        });

        Schema::whenTableDoesntHaveColumn('records', 'type', function (Blueprint $table) {
            $table->addColumn('integer', 'type')->nullable();
        });

        Schema::whenTableDoesntHaveColumn('records_events', 'meta', function (Blueprint $table) {
            $table->addColumn('text', 'meta')->nullable();
        });

        Schema::whenTableDoesntHaveColumn('conference_participants', 'role', function (Blueprint $table) {
            $table->addColumn('integer', 'role', ['default' => 0]);
        });

        Schema::whenTableDoesntHaveColumn('alerts', 'status', function (Blueprint $table) {
            $table->addColumn('integer', 'status')->nullable();
        });

        Schema::whenTableDoesntHaveColumn('cache', 'expiry', function (Blueprint $table) {
            $table->addColumn('integer', 'expiry')->nullable();
        });

        Schema::whenTableDoesntHaveColumn('cache_records_conference_participants', 'service_body_id', function (Blueprint $table) {
            $table->addColumn('integer', 'service_body_id')->nullable();
        });

        Schema::whenTableDoesntHaveColumn('metrics', 'service_body_id', function (Blueprint $table) {
            $table->addColumn('integer', 'service_body_id', ['unsigned' => true, 'nullable' => true]);
        });

        Schema::whenTableDoesntHaveColumn('users', 'service_bodies', function (Blueprint $table) {
            $table->addColumn('text', 'service_bodies')->nullable();
        });
    }

    public function down(): void
    {
        Schema::whenTableHasColumn('records_events', 'type', function (Blueprint $table) {
            $table->removeColumn('type');
        });

        Schema::whenTableHasColumn('records', 'type', function (Blueprint $table) {
            $table->removeColumn('type');
        });

        Schema::whenTableHasColumn('records_events', 'meta', function (Blueprint $table) {
            $table->removeColumn('meta');
        });

        Schema::whenTableHasColumn('conference_participants', 'role', function (Blueprint $table) {
            $table->removeColumn('role');
        });

        Schema::whenTableHasColumn('alerts', 'status', function (Blueprint $table) {
            $table->removeColumn('status');
        });

        Schema::whenTableHasColumn('cache', 'expiry', function (Blueprint $table) {
            $table->removeColumn('expiry');
        });

        Schema::whenTableHasColumn('cache_records_conference_participants', 'service_body_id', function (Blueprint $table) {
            $table->removeColumn('service_body_id');
        });

        Schema::whenTableHasColumn('metrics', 'service_body_id', function (Blueprint $table) {
            $table->removeColumn('service_body_id');
        });

        Schema::whenTableHasColumn('users', 'service_bodies', function (Blueprint $table) {
            $table->removeColumn('service_bodies');
        });
    }
};
