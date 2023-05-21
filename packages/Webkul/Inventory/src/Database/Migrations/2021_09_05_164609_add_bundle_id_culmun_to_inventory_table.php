<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBundleIdCulmunToInventoryTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('inventory_areas', function (Blueprint $table) {
            $table->unsignedInteger('bundle_id')->nullable()->after('product_id');
            $table->foreign('bundle_id')->references('id')->on('bundles')->onDelete('cascade');
        });
        Schema::table('inventory_warehouses', function (Blueprint $table) {
            $table->unsignedInteger('bundle_id')->nullable()->after('product_id');
            $table->foreign('bundle_id')->references('id')->on('bundles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('inventory_areas', function (Blueprint $table) {
            $table->dropForeign('inventory_areas_bundle_id_foreign');
            $table->dropColumn('bundle_id');
        });
        Schema::table('inventory_warehouses', function (Blueprint $table) {
            $table->dropForeign('inventory_warehouses_bundle_id_foreign');
            $table->dropColumn('bundle_id');
        });
    }

}
