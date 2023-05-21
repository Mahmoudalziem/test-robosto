<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWalletColumnToAreasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->unsignedDecimal('wallet', 8, 2)->default(0)->after('status');
            $table->unsignedDecimal('total_wallet', 12, 2)->default(0)->after('wallet');
            $table->unsignedDecimal('pending_wallet', 9, 2)->default(0)->after('total_wallet');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn('wallet');
            $table->dropColumn('total_wallet');
            $table->dropColumn('pending_wallet');
        });
    }
}
