<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {

            $table->decimal('price', 12, 4)->default(0)->unsigned()->change();
            $table->decimal('base_price', 12, 4)->default(0)->unsigned()->change();
            $table->decimal('total', 12, 4)->default(0)->unsigned()->change();
            $table->decimal('base_total', 12, 4)->default(0)->unsigned()->change();
            $table->decimal('total_invoiced', 12, 4)->default(0)->unsigned()->change();
            $table->decimal('base_total_invoiced', 12, 4)->default(0)->unsigned()->change();
            $table->decimal('amount_refunded', 12, 4)->default(0)->unsigned()->change();
            $table->decimal('base_amount_refunded', 12, 4)->default(0)->unsigned()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
