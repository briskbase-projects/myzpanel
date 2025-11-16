<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sync_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type')->unique(); // 'incremental' or 'full'
            $table->timestamp('last_sync_time')->nullable();
            $table->timestamp('last_successful_sync')->nullable();
            $table->boolean('is_running')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sync_tracking');
    }
}
