<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBonusVariablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bonus_variables', function (Blueprint $table) {
            $table->id();

            $table->string('orders')->nullable();
            $table->string('orders_bonus')->nullable();

            $table->string('working_hours')->nullable();
            $table->string('working_hours_bonus')->nullable();

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
        Schema::dropIfExists('bonus_variables');
    }
}
