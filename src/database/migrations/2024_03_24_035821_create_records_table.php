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
        if (!Schema::hasTable('records')) {
            Schema::create('records', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('callsid')->index('idx_records_callsid');
                $table->timestamp('start_time')->nullable();
                $table->timestamp('end_time')->nullable();
                $table->string('from_number');
                $table->string('to_number');
                $table->longText('payload')->nullable();
                $table->integer('duration');
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
        Schema::dropIfExists('records');
    }
};
