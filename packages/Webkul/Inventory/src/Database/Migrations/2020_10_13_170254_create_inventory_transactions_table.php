<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('from_warehouse_id')->unsigned();
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');

            $table->integer('to_warehouse_id')->unsigned();
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->integer('from_area_id')->unsigned()->default(1);
            $table->integer('to_area_id')->unsigned()->default(1);              
            $table->foreign('from_area_id')->references('id')->on('areas')->onDelete('cascade');            
            $table->foreign('to_area_id')->references('id')->on('areas')->onDelete('cascade');
            
            $table->tinyInteger('status')->default(1)->comment('0 => Cancelled, 1 => Pending, 2 => on-the-way, 3 => transferred');
            $table->enum('transaction_type',['inside','outside'])->nullable(); // inside (same area) outside (another area)

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
        Schema::dropIfExists('inventory_transactions');
    }
}
