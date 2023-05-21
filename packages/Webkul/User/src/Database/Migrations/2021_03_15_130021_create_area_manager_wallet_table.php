<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAreaManagerWalletTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('area_manager_wallet', function (Blueprint $table) {

            $table->increments('id');
            
            $table->unsignedInteger('area_manager_id')->nullable();
            $table->foreign('area_manager_id')->references('id')->on('admins')->onDelete('cascade');

            $table->unsignedDecimal('wallet', 8, 2)->default(0)->nullable();
            $table->unsignedDecimal('total_wallet', 12, 2)->default(0)->nullable();
            $table->unsignedDecimal('pending_wallet', 9, 2)->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('area_manager_wallet');
    }
}
