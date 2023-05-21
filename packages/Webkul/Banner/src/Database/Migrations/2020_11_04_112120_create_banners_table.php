<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banners', function (Blueprint $table) {

            $table->increments('id');
            $table->unsignedInteger('area_id')->nullable();
            $table->string('name')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->unsignedInteger('position')->nullable()->default(0);
            $table->unsignedInteger('action_id')->nullable()->default(0);
            $table->enum('actionable_type',['Category','SubCategory','Product','InviteFriend','Promotion'])->nullable();
            $table->enum('section',['sale','deal'])->nullable()->default('sale');
            $table->boolean('status')->nullable()->default(0);
            $table->boolean('default')->nullable()->default(0);
            $table->string('image_ar')->nullable();
            $table->string('image_en')->nullable();
            $table->index('area_id');
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
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
        Schema::dropIfExists('banners');
    }
}
