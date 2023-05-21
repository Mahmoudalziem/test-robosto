<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('area_id')->nullable();
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->string('name');
            $table->string('address');
            $table->string('image')->nullable();
            $table->string('image_id')->nullable();
            $table->string('id_number')->nullable()->unique();
            $table->date('liecese_validity_date')->nullable();
            $table->string('username')->nullable()->unique();
            $table->string('phone_private')->nullable();
            $table->string('phone_work');
            $table->string('liecese_validity_no')->nullable();;
            $table->string('email')->unique();
            $table->string('password');
            $table->string('api_token', 80)->unique()->nullable()->default(null);;
            $table->string('token')->nullable();
            $table->rememberToken();
            $table->enum('availability',['idle', 'delivery', 'back', 'break', 'emergency', 'online', 'offline', 'transaction'])->default('offline');
            $table->boolean('is_online')->default(0)->nullable(); // online / offline
            $table->boolean('status')->default(0); // Admin controlling driver
            $table->decimal('wallet', 7, 2)->default(0);
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
        Schema::dropIfExists('drivers');
    }
}
