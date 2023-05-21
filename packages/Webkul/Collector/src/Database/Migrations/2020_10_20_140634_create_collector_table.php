<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collectors', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('area_id')->nullable();
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->string('id_number')->nullable();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('image')->nullable();
            $table->string('image_id')->nullable();
            $table->string('phone_private')->nullable();
            $table->string('phone_work');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('availability',['online',  'offline'])->default('offline');
            $table->boolean('is_online')->default(0)->nullable(); // online / offline
            $table->boolean('status')->default(0); // Admin controlling driver
            $table->index('area_id');
            $table->index('warehouse_id');
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
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
        Schema::dropIfExists('collectors');
    }
}
