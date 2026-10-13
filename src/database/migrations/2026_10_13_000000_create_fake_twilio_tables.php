<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backing store for fake Twilio mode (TWILIO_FAKE_MODE). The fake REST client
 * records every outbound Twilio API request here and keeps just enough call and
 * conference state for the call flows to work without api.twilio.com. These
 * tables stay empty unless fake mode is enabled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fake_twilio_requests', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10);
            $table->text('url');
            $table->string('resource', 64)->index(); // e.g. calls.create, messages.create
            $table->json('params')->nullable(); // query string params
            $table->json('data')->nullable(); // form body params
            $table->unsignedSmallInteger('response_status');
            $table->json('response')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('fake_twilio_calls', function (Blueprint $table) {
            $table->string('sid', 64)->primary();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->string('status', 32);
            $table->string('direction', 32);
            $table->string('phone_number_sid', 64)->nullable();
            $table->text('url')->nullable();
            $table->text('status_callback')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('fake_twilio_conference_participants', function (Blueprint $table) {
            $table->id();
            $table->string('friendly_name')->index();
            $table->string('call_sid', 64);
            $table->timestamps();

            $table->unique(['friendly_name', 'call_sid'], 'fake_twilio_participant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fake_twilio_conference_participants');
        Schema::dropIfExists('fake_twilio_calls');
        Schema::dropIfExists('fake_twilio_requests');
    }
};
