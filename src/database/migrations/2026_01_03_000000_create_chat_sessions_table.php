<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('client_id')->index(); // Browser session identifier
            $table->string('volunteer_phone')->nullable(); // Phone number of responding volunteer
            $table->unsignedInteger('service_body_id')->nullable();
            $table->enum('status', ['pending', 'active', 'closed'])->default('pending');
            $table->json('messages')->default('[]'); // Array of message objects
            $table->string('location')->nullable(); // User's location for routing
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_activity_at']);
            $table->index('volunteer_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
