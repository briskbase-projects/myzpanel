<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductTitleToSyncedOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('synced_order_items', function (Blueprint $table) {
            $table->string('product_title')->nullable()->after('sku');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('synced_order_items', function (Blueprint $table) {
            $table->dropColumn('product_title');
        });
    }
}
