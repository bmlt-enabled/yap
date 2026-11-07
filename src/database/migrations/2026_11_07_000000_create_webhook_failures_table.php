<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Runtime failures of Twilio-facing webhooks (bmlt-enabled/yap#1686).
 *
 * One row per 5xx returned on a twilio.signature route, so System Health can
 * show what callers heard as "an application error has occurred". Rows are
 * pruned by WebhookFailureService; they hold the CallSid (already stored on
 * call records) but never the caller's number or anything they said or typed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webhook_failures')) {
            return;
        }

        Schema::create('webhook_failures', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('created_at')->nullable()->index();
            $table->string('method', 8);
            $table->string('route', 191);
            $table->unsignedSmallInteger('status');
            $table->string('callsid', 64)->nullable()->index();
            $table->string('exception_class', 255)->nullable();
            $table->text('message')->nullable();
            $table->string('location', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_failures');
    }
};
