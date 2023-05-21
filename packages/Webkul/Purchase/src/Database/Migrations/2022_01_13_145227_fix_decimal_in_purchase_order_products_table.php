<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixDecimalInPurchaseOrderProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_order_products', function (Blueprint $table) {
            $table->decimal('cost_before_discount', 12, 4)->nullable()->change();
            $table->decimal('cost', 12, 4)->nullable()->change();
            $table->decimal('amount_before_discount', 12, 4)->nullable()->change();
            $table->decimal('amount', 12, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_order_products', function (Blueprint $table) {
            //
        });
    }
}
