<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('records_events')) {
            Schema::create('records_events', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('callsid')->index('idx_records_events_callsid');
                $table->timestamp('event_time')->nullable();
                $table->integer('event_id');
                $table->integer('service_body_id')->nullable()->index('idx_records_events_service_body_id');
                $table->text('meta')->nullable();
                $table->integer('type')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('records_events');
    }
};
