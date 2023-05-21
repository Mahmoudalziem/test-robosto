<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsInventoryControlCulumnToInventoryAdjustmentsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->boolean('is_inventory_control')->default(0)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->dropColumn('is_inventory_control');
        });
    }

}
