<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectorDeviceTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collector_device_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('collector_id')->nullable();
            $table->string('token')->nullable()->default(null);;
            $table->index('collector_id');
            $table->index('token');
            $table->foreign('collector_id')->references('id')->on('collectors')->onDelete('cascade');
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
        Schema::dropIfExists('collector_device_tokens');
    }
}
