<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionSubCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotion_sub_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('promotion_apply_id');
            $table->unsignedInteger('promotion_id')->nullable();
            $table->unsignedInteger('sub_category_id');

            $table->foreign('promotion_apply_id')->references('id')->on('promotion_applies')->onDelete('cascade');
            $table->foreign('sub_category_id')->references('id')->on('sub_categories')->onDelete('cascade');
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
        Schema::dropIfExists('promotion_sub_categories');
    }
}
