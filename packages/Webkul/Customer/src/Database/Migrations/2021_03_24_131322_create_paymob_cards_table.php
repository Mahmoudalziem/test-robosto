<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymobCardsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('paymob_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('last_four', 20);
            $table->string('token');
            $table->string('order_id')->nullable();
            $table->enum('brand', ['visa', 'mastercard', 'meeza'])->nullable();
            $table->string('email');
            $table->tinyInteger("is_default")->default(1);
            $table->unsignedInteger('customer_id')->nullable;
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('paymob_cards');
    }

}
