<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBundleIdCulumnToOldOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('old_order_items', function (Blueprint $table) {
            $table->unsignedInteger('bundle_id')->nullable()->after('product_id');
            $table->foreign('bundle_id')->references('id')->on('bundles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('old_order_items', function (Blueprint $table) {
            $table->dropForeign('old_order_items_bundle_id_foreign');
            $table->dropColumn('bundle_id');
        });
    }
}
