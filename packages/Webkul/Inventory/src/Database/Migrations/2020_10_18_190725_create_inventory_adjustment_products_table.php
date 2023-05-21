<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryAdjustmentProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_adjustment_products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('inventory_adjustment_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->string('sku')->nullable();
            $table->integer('qty_stock_before')->nullable();
            $table->integer('qty')->nullable();
            $table->integer('qty_stock_after')->nullable();
            $table->string('image')->nullable();
            $table->text('note')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1 => Lost, 2 => Expired, 3 => Over Qty, 4 => Damaged , 5 => ٌReturn to Vendor');
            $table->timestamps();

            $table->foreign('inventory_adjustment_id')->references('id')->on('inventory_adjustments')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_adjustment_products');
    }
}
