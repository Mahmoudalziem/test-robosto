<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('sub_refunded', 12, 2)->default(0)->nullable()->unsigned()->change();
            $table->decimal('final_refunded', 12, 2)->default(0)->nullable()->unsigned()->change();
            $table->decimal('sub_total', 12, 2)->default(0)->nullable()->unsigned()->change();
            $table->decimal('final_total', 12, 2)->default(0)->nullable()->unsigned()->change();

            $table->decimal('discount', 12, 2)->default(0)->nullable()->unsigned()->change();
            $table->decimal('tax_amount', 12, 2)->default(0)->nullable()->unsigned()->change();
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
