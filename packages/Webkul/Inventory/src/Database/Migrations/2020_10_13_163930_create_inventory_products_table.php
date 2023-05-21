<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sku')->nullable();
            $table->date('prod_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->integer('qty')->nullable()->unsigned();
            $table->decimal('cost_before_discount', 6, 2)->nullable()->unsigned();
            $table->decimal('cost', 6, 2)->nullable()->unsigned();
            $table->decimal('amount_before_discount', 8, 2)->nullable()->unsigned();
            $table->decimal('amount', 8, 2)->nullable()->unsigned();

            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->integer('warehouse_id')->unsigned();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');

            $table->integer('area_id')->unsigned();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');

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
        Schema::dropIfExists('inventory_products');
    }
}
