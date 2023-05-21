<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryAdjustmentActionsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('inventory_adjustment_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('inventory_adjustment_id')->unsigned();
            $table->enum('action', ['pending', 'cancelled', 'approved'])->default('pending')->comment('0 => Cancelled, 1 => Pending, 2 => Approved')->nullable();            
            $table->enum('admin_type', ['admin', 'collector'])->nullable();
            $table->integer('admin_id')->unsigned();            
            $table->foreign('inventory_adjustment_id')->references('id')->on('inventory_adjustments')->onDelete('cascade');            
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('inventory_adjustment_actions');
    }

}
