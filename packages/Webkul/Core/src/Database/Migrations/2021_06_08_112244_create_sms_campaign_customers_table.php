<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsCampaignCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_campaign_customers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('sms_campaign_id');
            $table->unsignedInteger('customer_id');
            $table->foreign('sms_campaign_id')->references('id')->on('sms_campaigns')->onDelete('cascade')->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade')->nullable();
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
        Schema::dropIfExists('sms_campaign_customers');
    }
}
