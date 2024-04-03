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
        Schema::create('event_status', function (Blueprint $table) {
            $table->bigInteger('id', true)->unique('id_unique');
            $table->string('callsid')->nullable();
            $table->integer('status')->nullable();
            $table->integer('event_id')->nullable();
            $table->timestamps();

            //$table->primary(['id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_status');
    }
};
