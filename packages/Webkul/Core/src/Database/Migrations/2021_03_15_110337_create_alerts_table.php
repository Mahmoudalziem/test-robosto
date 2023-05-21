<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlertsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->increments('id');
            $table->json('admin_type'); // role (area_manager)             
            $table->string('key')->nullable();// key: data will be open after direct to  like (driver_logs,)
            $table->string('model')->nullable();// driver ,orders etc            
            $table->unsignedInteger('model_id')->nullable();// driver ,orders etc
            $table->text('direct_to')->nullable(); // route name
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
        Schema::dropIfExists('alerts');
    }
}
