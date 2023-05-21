<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeleteColumnToManyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('drivers', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('customers', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('collectors', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('admins', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('areas', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('promotions', function (Blueprint $table) {
            $table->softDeletes();
        });
        
        Schema::table('warehouses', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('customers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('collectors', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('admins', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('areas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
