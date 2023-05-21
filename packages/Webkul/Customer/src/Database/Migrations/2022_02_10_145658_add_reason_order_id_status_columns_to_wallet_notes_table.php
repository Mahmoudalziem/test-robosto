<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReasonOrderIdStatusColumnsToWalletNotesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('wallet_notes', function (Blueprint $table) {
            $table->unsignedInteger('reason_id')->nullable()->after('admin_id');
            $table->unsignedInteger('order_id')->nullable()->after('admin_id');
            $table->enum('status', ['pending', 'cancelled', 'approved'])->default('pending')->nullable()->after('admin_id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('reason_id')->references('id')->on('wallet_customer_reasons')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('wallet_notes', function (Blueprint $table) {
            $table->dropForeign(['reason_id']);
            $table->dropColumn('reason_id');
            $table->dropColumn('status');
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });
    }

}
