<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletCustomerReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_customer_reasons', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reason');
            $table->enum('type', ['none','amount', 'product'])->default('none');
            $table->boolean('is_added')->default(0);
            $table->boolean('is_reduced')->default(0);
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
        Schema::dropIfExists('wallet_customer_reasons');
    }
}
