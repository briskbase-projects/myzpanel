<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrentPageToSyncTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sync_tracking', function (Blueprint $table) {
            $table->integer('current_page')->default(0)->after('total_orders');
            $table->integer('total_pages')->default(0)->after('current_page');
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
            $table->dropColumn(['current_page', 'total_pages']);
        });
    }
}
