<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountsToSyncTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sync_tracking', function (Blueprint $table) {
            $table->integer('synced_count')->default(0)->after('error_message');
            $table->integer('updated_count')->default(0)->after('synced_count');
            $table->integer('failed_count')->default(0)->after('updated_count');
            $table->integer('total_orders')->default(0)->after('failed_count');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sync_tracking', function (Blueprint $table) {
            $table->dropColumn(['synced_count', 'updated_count', 'failed_count', 'total_orders']);
        });
    }
}
