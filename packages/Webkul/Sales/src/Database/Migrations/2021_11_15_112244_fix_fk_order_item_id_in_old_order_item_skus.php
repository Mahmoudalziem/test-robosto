<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixFkOrderItemIdInOldOrderItemSkus extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::table('old_order_items_skus', function (Blueprint $table) {
            //table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');            
            $table->dropForeign('old_order_items_skus_order_item_id_foreign');
            $table->dropColumn('order_item_id');

            $table->integer('old_order_item_id')->unsigned()->after('order_id')->nullable();
            $table->foreign('old_order_item_id')->references('id')->on('old_order_items')->onDelete('cascade');
        });
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::table('old_order_items_skus', function (Blueprint $table) {
            $table->integer('order_item_id')->unsigned()->nullable();
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');

            $table->dropForeign(['old_order_item_id']);
            $table->dropColumn('old_order_item_id');
        });
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

}
