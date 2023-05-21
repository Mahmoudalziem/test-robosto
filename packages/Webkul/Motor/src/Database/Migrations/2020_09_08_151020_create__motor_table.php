<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMotorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('motors', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('area_id')->nullable();
            $table->string('chassis_no');
            $table->string('license_plate_no');
            $table->text('condition');
            $table->string('image')->nullable();
            $table->boolean('status')->default(1);
            $table->index('area_id');
            $table->timestamps();
        });

        Schema::table('motors', function (Blueprint $table) {

            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('motors');
    }
}
