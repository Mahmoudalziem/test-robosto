<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeWalletLengthInDriverTransactionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('driver_transaction_requests', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->default(0)->unsigned()->change();
            $table->decimal('current_wallet', 12, 2)->default(0)->unsigned()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('driver_transaction_requests', function (Blueprint $table) {
            //
        });
    }
}
