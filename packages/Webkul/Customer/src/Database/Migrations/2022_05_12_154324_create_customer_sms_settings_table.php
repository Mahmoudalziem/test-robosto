<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerSmsSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_sms_settings', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('customer_id')->unsigned();
            $table->string('sms_type'); // 3_orders_5_stars then send sms once
            $table->boolean('sent')->default(0);

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->unique(["customer_id", "sms_type"]);

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
        Schema::dropIfExists('customer_sms_settings');
    }
}
