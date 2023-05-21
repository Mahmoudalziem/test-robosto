<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAreaOpenHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('area_open_hours', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('area_id');
            $table->unsignedInteger('rank');        
            $table->enum('from_day',['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']);            
            $table->time('from_hour'); // hours/mins
            $table->enum('to_day',['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']);
            $table->time('to_hour');  // hours/mins           
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade')->nullable();
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
        Schema::dropIfExists('area_open_hours');
    }
}
