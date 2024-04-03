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
        Schema::create('cache_records_conference_participants', function (Blueprint $table) {
            $table->string('parent_callsid', 100)->nullable()->index('idx_rcp_parent_parent_callsid');
            $table->string('callsid', 100)->nullable()->index('idx_rcp_parent_callsid');
            $table->string('guid', 36)->nullable();
            $table->integer('service_body_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cache_records_conference_participants');
    }
};
