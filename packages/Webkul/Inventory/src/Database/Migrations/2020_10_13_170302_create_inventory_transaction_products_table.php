<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTransactionProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_transaction_products', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('qty', 9, 3)->nullable();
            $table->integer('inventory_transaction_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->string('sku')->nullable();
            $table->integer('inventory_product_id')->unsigned();
            $table->timestamps();

            $table->foreign('inventory_transaction_id')->references('id')->on('inventory_transactions')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('inventory_product_id')->references('id')->on('inventory_products')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_transaction_products');
    }
}
