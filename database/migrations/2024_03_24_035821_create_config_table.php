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
        Schema::create('config', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('service_body_id');
            $table->mediumText('data');
            $table->string('data_type', 45);
            $table->unsignedInteger('parent_id')->nullable();
            $table->integer('status')->nullable();

            $table->unique(['service_body_id', 'data_type', 'parent_id'], 'service_body_id_data_type_parent_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('config');
    }
};
