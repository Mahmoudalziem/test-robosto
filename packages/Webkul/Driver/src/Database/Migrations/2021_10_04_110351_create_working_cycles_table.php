<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkingCyclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('working_cycles', function (Blueprint $table) {
            $table->id();

            $table->enum('status', ['active', 'done'])->default('active');

            $table->timestamp('expected_from')->nullable();
            $table->timestamp('expected_to')->nullable();
            $table->timestamp('expected_back')->nullable();
            $table->unsignedInteger('expected_back_distance')->nullable();
            $table->unsignedSmallInteger('expected_time')->nullable()->comment("In Minutes");

            $table->timestamp('actual_from')->nullable();
            $table->timestamp('actual_to')->nullable();
            $table->timestamp('actual_back')->nullable();
            $table->unsignedSmallInteger('actual_time')->nullable()->comment("In Minutes");

            $table->smallInteger('target')->nullable();
            $table->unsignedInteger('distance')->nullable();
            $table->json('path')->nullable();

            $table->unsignedInteger('driver_id')->nullable();
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');

            $table->unsignedInteger('area_id')->nullable();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');

            $table->unsignedInteger('warehouse_id')->nullable();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');

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
        Schema::dropIfExists('working_cycles');
    }
}
