<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryStockValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_stock_values', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('area_id')->unsigned()->nullable() ;             
            $table->integer('warehouse_id')->unsigned()->nullable();
            $table->unsignedDecimal('amount_before_discount', 12, 4)->default(0)->nullable() ;
            $table->unsignedDecimal('amount', 12, 4)->default(0)->nullable() ;            
            $table->date('build_date') ;            
            $table->timestamps();
            
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');                       
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_stock_values');
    }
}
