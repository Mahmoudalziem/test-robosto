<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOldOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('old_order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sku')->nullable();

            $table->string('shelve_name')->nullable();
            $table->smallInteger('shelve_position')->nullable();

            $table->decimal('weight', 12, 4)->default(0)->nullable();

            $table->integer('qty_ordered')->default(0)->nullable();
            $table->integer('qty_shipped')->default(0)->nullable();
            $table->integer('qty_invoiced')->default(0)->nullable();
            $table->integer('qty_canceled')->default(0)->nullable();
            $table->integer('qty_returned')->default(0)->nullable();
            $table->integer('qty_refunded')->default(0)->nullable();
            $table->string('return_reason')->nullable();

            $table->decimal('price', 12, 4)->unsigned()->default(0);
            $table->decimal('base_price', 12, 4)->unsigned()->default(0);
            

            $table->decimal('total', 12, 4)->unsigned()->default(0);
            $table->decimal('base_total', 12, 4)->unsigned()->default(0);

            $table->decimal('total_invoiced', 12, 4)->unsigned()->default(0);
            $table->decimal('base_total_invoiced', 12, 4)->unsigned()->default(0);
            $table->decimal('amount_refunded', 12, 4)->unsigned()->default(0);
            $table->decimal('base_amount_refunded', 12, 4)->unsigned()->default(0);

            $table->decimal('discount_percent', 12, 4)->default(0)->nullable();
            $table->enum('discount_type', ['val', 'per'])->default('val');
            $table->decimal('discount_amount', 12, 4)->default(0)->nullable();
            $table->decimal('base_discount_amount', 12, 4)->default(0)->nullable();
            $table->decimal('discount_invoiced', 12, 4)->default(0)->nullable();
            $table->decimal('base_discount_invoiced', 12, 4)->default(0)->nullable();
            $table->decimal('discount_refunded', 12, 4)->default(0)->nullable();
            $table->decimal('base_discount_refunded', 12, 4)->default(0)->nullable();

            $table->decimal('tax_percent', 12, 4)->default(0)->nullable();
            $table->decimal('tax_amount', 12, 4)->default(0)->nullable();
            $table->decimal('base_tax_amount', 12, 4)->default(0)->nullable();
            $table->decimal('tax_amount_invoiced', 12, 4)->default(0)->nullable();
            $table->decimal('base_tax_amount_invoiced', 12, 4)->default(0)->nullable();
            $table->decimal('tax_amount_refunded', 12, 4)->default(0)->nullable();
            $table->decimal('base_tax_amount_refunded', 12, 4)->default(0)->nullable();

            $table->integer('product_id')->unsigned()->nullable();
            $table->integer('order_id')->unsigned()->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->json('additional')->nullable();
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
        Schema::dropIfExists('old_order_items');
    }
}
