<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsCampaignTagsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('sms_campaign_tags', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('sms_campaign_id');
            $table->unsignedInteger('tag_id');
            $table->foreign('sms_campaign_id')->references('id')->on('sms_campaigns')->onDelete('cascade')->nullable();
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade')->nullable();
            $table->timestamps();
        });
 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('sms_campaign_tags');
    }

}
