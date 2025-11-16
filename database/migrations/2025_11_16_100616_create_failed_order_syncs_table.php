<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFailedOrderSyncsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('failed_order_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->index();
            $table->string('order_number')->nullable();
            $table->string('order_item_id')->nullable();
            $table->string('sku')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->string('sync_type')->default('incremental'); // 'full' or 'incremental'
            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by')->nullable(); // admin user who resolved it
            $table->text('resolution_notes')->nullable();
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
        Schema::dropIfExists('failed_order_syncs');
    }
}
