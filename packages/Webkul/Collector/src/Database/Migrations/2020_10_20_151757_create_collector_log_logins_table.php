<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectorLogLoginsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collector_log_logins', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('collector_id')->nullable();
            $table->enum('action',['online','offline'])->nullable();
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
        Schema::dropIfExists('collector_log_logins');
    }
}
