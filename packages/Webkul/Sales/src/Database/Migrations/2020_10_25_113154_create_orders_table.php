<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('increment_id');
            $table->enum('status',['scheduled','pending', 'waiting_customer_response', 'preparing', 'ready_to_pickup', 'on_the_way', 'at_place', 'delivered', 'returned', 'cancelled', 'cancelled_for_items', 'emergency_failure'])->default('pending');
            $table->boolean('in_queue')->default(0);
            $table->tinyInteger('flagged')->default(0);

            $table->string('coupon_code')->nullable();

            $table->integer('items_count')->nullable();
            $table->integer('items_shipped_count')->nullable();
            $table->integer('items_qty_ordered')->nullable();
            $table->integer('items_qty_shipped')->nullable();
            $table->integer('items_qty_cancelled')->nullable();
            $table->integer('items_qty_return')->nullable();
            $table->integer('items_qty_refunded')->nullable();

            $table->decimal('sub_refunded', 12, 2)->default(0)->nullable();
            $table->decimal('final_refunded', 12, 2)->default(0)->nullable();

            $table->decimal('sub_total', 12, 2)->default(0)->nullable();
            $table->decimal('final_total', 12, 2)->default(0)->nullable();

            $table->string('discount_type')->nullable();
            $table->decimal('discount', 12, 2)->default(0)->nullable();

            $table->decimal('tax_amount', 12, 2)->default(0)->nullable();
            $table->tinyInteger('delivery_chargs')->default(0);

            $table->text('note')->nullable();
            $table->text('cancelled_reason')->nullable();

            $table->integer('customer_id')->unsigned()->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->integer('address_id')->unsigned()->nullable();
            $table->foreign('address_id')->references('id')->on('customer_addresses')->onDelete('set null');
            $table->integer('channel_id')->unsigned()->nullable();
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('set null');
            $table->integer('area_id')->unsigned();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->integer('warehouse_id')->unsigned()->nullable();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->integer('driver_id')->unsigned()->nullable();
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('set null');
            $table->integer('collector_id')->unsigned()->nullable();
            $table->foreign('collector_id')->references('id')->on('collectors')->onDelete('set null');
            $table->integer('aggregator_id')->unsigned()->nullable();
            $table->timestamp('scheduled_at')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
