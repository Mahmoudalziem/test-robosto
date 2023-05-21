<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shippments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('shipping_number')->nullable();
            $table->integer('shipper_id')->unsigned();
            $table->foreign('shipper_id')->references('id')->on('shippers')->onDelete('cascade');
            $table->integer('area_id')->unsigned();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->integer('warehouse_id')->unsigned();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->integer('customer_address_id')->unsigned()->nullable();
            $table->dateTime('first_trial_date')->nullable();
            $table->integer('shipping_address_id')->unsigned();
            $table->foreign('shipping_address_id')->references('id')->on('shipping_addresses')->onDelete('cascade');
            $table->integer('pickup_location_id')->unsigned();
            $table->foreign('pickup_location_id')->references('id')->on('shippers_pickup_locations')->onDelete('cascade');
            $table->integer('items_count')->nullable();
            $table->decimal('final_total', 12, 2)->default(0)->nullable();
            $table->text('note')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->enum('status',['pending','scheduled','rescheduled', 'on_the_way', 'delivered', 'failed'])->default('pending');
            $table->enum('current_status',['pending_picking_up_items','pending_collecting_customer_info','pending_transfer', 'pending_ready_for_dispatching', 'failed_collecting_customer_info', 'failed_picking_up_items', 'dispatching', 'delivered', 'failed'])->default('pending_picking_up_items');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shippments');
    }
}
