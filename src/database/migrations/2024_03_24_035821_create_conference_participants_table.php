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
        if (!Schema::hasTable('conference_participants')) {
            Schema::create('conference_participants', function (Blueprint $table) {
                $table->integer('id', true);
                $table->timestamp('timestamp')->useCurrent();
                $table->string('conferencesid', 100)->index('idx_conference_participants_conferencesid');
                $table->string('callsid', 100)->index('idx_conference_participants_callsid');
                $table->string('friendlyname', 100);
                $table->integer('role');

                ///$table->primary(['id']);
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
        Schema::dropIfExists('conference_participants');
    }
};
