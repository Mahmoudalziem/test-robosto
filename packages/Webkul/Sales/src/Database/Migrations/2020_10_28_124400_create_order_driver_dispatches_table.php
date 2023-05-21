<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDriverDispatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_driver_dispatches', function (Blueprint $table) {
            $table->id();
            $table->integer('dispatched_at')->nullable();
            $table->enum('status',['not_send','pending','cancelled'])->default('not_send');
            $table->text('reason')->nullable();
            $table->tinyInteger('rank')->default(0);
            $table->integer('delivery_time')->nullable();
            $table->tinyInteger('trial')->default(0);
            $table->integer('order_id')->unsigned()->nullable();
            $table->integer('warehouse_id')->unsigned()->nullable();
            $table->integer('driver_id')->unsigned()->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
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
        Schema::dropIfExists('order_driver_dispatches');
    }
}
