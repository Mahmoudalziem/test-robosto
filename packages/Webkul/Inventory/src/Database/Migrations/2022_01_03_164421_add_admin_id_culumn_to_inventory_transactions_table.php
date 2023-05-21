<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminIdCulumnToInventoryTransactionsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->integer('admin_id')->unsigned()->nullable()->after('status');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('inventory_transactions', function (Blueprint $table) {
           $table->dropColumn('admin_id');
        });        
    }

}
