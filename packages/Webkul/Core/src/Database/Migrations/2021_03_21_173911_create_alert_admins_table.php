<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlertAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alert_admins', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('alert_id')->unsigned(); // user who recieve the notificatin            
            $table->integer('admin_id')->unsigned(); // user who recieve the notificatin
            $table->boolean('read')->default(0); 
            $table->foreign('alert_id')->references('id')->on('alerts')->onDelete('cascade');            
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');             
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
        Schema::dropIfExists('alert_admins');
    }
}
