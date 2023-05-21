<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletCustomerItemsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('wallet_customer_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('wallet_note_id')->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->integer('product_id')->unsigned()->nullable();
            $table->integer('qty')->default(0)->nullable();
            $table->decimal('price', 12, 4)->default(0)->nullable();

            $table->foreign('wallet_note_id')->references('id')->on('wallet_notes')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('wallet_customer_items');
    }

}
