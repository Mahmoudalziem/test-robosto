<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippmentTransferTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shippment_transfer', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('from_warehouse_id')->unsigned();
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->integer('to_warehouse_id')->unsigned();
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');

            $table->enum('status',['cancelled','pending', 'on_the_way', 'transferred'])->default('pending');
            
            $table->integer('shippment_id')->unsigned();
            $table->foreign('shippment_id')->references('id')->on('shippments')->onDelete('cascade');
            $table->integer('admin_id')->unsigned()->nullable();
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
        Schema::dropIfExists('shippment_transfer');
    }
}
