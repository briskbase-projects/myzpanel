<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncedOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('synced_order_items', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->index();
            $table->string('order_item_id')->index();
            $table->string('sku');
            $table->string('status')->nullable(); // sold, zurück, canceled
            $table->integer('sheet_row_number')->nullable(); // Google Sheet row number
            $table->timestamp('synced_at');
            $table->timestamp('updated_at')->nullable();

            // Composite unique index to prevent duplicates
            $table->unique(['order_number', 'order_item_id'], 'unique_order_item');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('synced_order_items');
    }
}
