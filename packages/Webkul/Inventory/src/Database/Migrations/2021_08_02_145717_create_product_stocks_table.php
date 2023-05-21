<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductStocksTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('product_stocks', function (Blueprint $table) {

            $table->increments('id');
            

            $table->integer('inventory_control_id')->unsigned();
            $table->foreign('inventory_control_id')->references('id')->on('inventory_controls')->onDelete('cascade');

            $table->integer('area_id')->unsigned();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');

            $table->integer('warehouse_id')->unsigned();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');

            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->integer('inventory_qty')->unsigned()->default(0)->nullable();
            $table->integer('shipped_qty')->unsigned()->default(0)->nullable();
            $table->integer('qty')->unsigned()->default(0)->nullable();
            $table->integer('qty_stock')->unsigned()->default(0)->nullable();
            $table->boolean('valid')->default(0); // 1 if inventory stock == collector stock 
            $table->boolean('status')->default(0); // 0 if collector did not stockaking the product

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('product_stocks');
    }

}
