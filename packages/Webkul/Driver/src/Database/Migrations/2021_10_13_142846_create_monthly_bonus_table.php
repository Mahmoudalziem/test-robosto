<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthlyBonusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monthly_bonus', function (Blueprint $table) {
            
            $table->id();

            $table->unsignedInteger('no_of_orders')->default(0);
            $table->unsignedDecimal('no_of_working_hours')->default(0);
            $table->unsignedDecimal('cutomer_ratings', 5, 2)->default(0);
            $table->unsignedDecimal('supervisor_ratings', 5, 2)->default(0);
            $table->unsignedDecimal('working_path_ratings', 5, 2)->default(0);
            $table->unsignedDecimal('orders_bonus', 8, 2)->default(0);
            $table->unsignedDecimal('working_hours_bonus', 8, 2)->default(0);
            $table->unsignedDecimal('back_bonus', 8, 2)->default(0);
            $table->unsignedInteger('no_of_orders_back_bonus')->default(0);
            $table->unsignedDecimal('bonus', 8, 2)->default(0);            
            $table->string('equation')->nullable();

            $table->unsignedInteger('driver_id')->nullable();
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');

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
        Schema::dropIfExists('monthly_bonus');
    }
}
