<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeWalletDecimalRangeInTheSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->decimal('wallet', 15, 4)->default(0)->change();
            $table->decimal('total_wallet', 15, 4)->default(0)->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('wallet', 15, 4)->default(0)->change();
        });

        Schema::table('area_manager_wallet', function (Blueprint $table) {
            $table->unsignedDecimal('wallet', 15, 4)->default(0)->change();
            $table->unsignedDecimal('total_wallet', 15, 4)->default(0)->change();
            $table->unsignedDecimal('pending_wallet', 15, 4)->default(0)->change();
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->unsignedDecimal('wallet', 15, 4)->default(0)->change();
            $table->unsignedDecimal('total_wallet', 15, 4)->default(0)->change();
            $table->unsignedDecimal('pending_wallet', 15, 4)->default(0)->change();
        });
        
        Schema::table('accountant_wallet', function (Blueprint $table) {
            $table->unsignedDecimal('wallet', 15, 4)->default(0)->change();
            $table->unsignedDecimal('total_wallet', 15, 4)->default(0)->change();
            $table->unsignedDecimal('pending_wallet', 15, 4)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
