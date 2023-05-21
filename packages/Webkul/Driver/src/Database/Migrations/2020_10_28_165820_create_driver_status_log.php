<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriverStatusLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('driver_id')->nullable();
            $table->unsignedInteger('period')->nullable();
            $table->dateTime('status_log_date')->nullable();
            $table->enum('availability',['online','offline','delivery','break', 'emergency', 'idle'])->nullable();
            $table->timestamps();
        });

        Schema::table('driver_status_logs', function (Blueprint $table) {

            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('driver_status_logs');
    }
}
