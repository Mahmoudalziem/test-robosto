<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('order_id')->nullable();  
            $table->unsignedInteger('paymob_card_id')->nullable();
            $table->string('paymob_order_id')->nullable();
            $table->string('paymob_transaction_id')->nullable();
            $table->float('amount')->nullable();               
            $table->json('payload_response')->nullable();
            $table->tinyInteger("is_paid")->default(0);
            $table->foreign('customer_id')->references('id')->on('customers')->nullable()->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->nullable()->onDelete('cascade');
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
        Schema::dropIfExists('customer_payments');
    }
}
