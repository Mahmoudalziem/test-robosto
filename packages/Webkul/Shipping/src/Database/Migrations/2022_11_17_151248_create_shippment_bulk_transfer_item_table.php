<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippmentBulkTransferItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shippment_bulk_transfer_item', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('transfer_id')->unsigned();
            $table->foreign('transfer_id')->references('id')->on('shippment_bulk_transfer')->onDelete('cascade');
            $table->integer('shippment_id')->unsigned();
            $table->foreign('shippment_id')->references('id')->on('shippments')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shippment_bulk_transfer_item');
    }
}
