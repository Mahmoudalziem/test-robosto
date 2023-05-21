<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewStatusToShippmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shippments', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `shippments` CHANGE `current_status` `current_status` ENUM('pending_picking_up_items','pending_collecting_customer_info','pending_transfer','pending_ready_for_dispatching','pending_distribution','pending_distributing','failed_collecting_customer_info','failed_picking_up_items','dispatching','delivered','failed','returned_to_vendor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_picking_up_items';");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shippments', function (Blueprint $table) {

        });
    }
}
