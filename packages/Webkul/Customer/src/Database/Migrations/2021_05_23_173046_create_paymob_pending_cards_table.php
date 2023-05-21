<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymobPendingCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paymob_pending_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('last_four', 20);
            $table->string('token');
            $table->string('order_id')->nullable();
            $table->enum('brand', ['visa', 'mastercard', 'meeza'])->nullable();
            $table->string('email');
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
        Schema::dropIfExists('paymob_pending_cards');
    }
}
