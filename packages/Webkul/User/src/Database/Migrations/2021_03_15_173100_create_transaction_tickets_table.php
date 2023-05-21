<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_tickets', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('area_manager_transaction_requests')->onDelete('cascade');

            $table->unsignedInteger('sender_id')->nullable();
            $table->foreign('sender_id')->references('id')->on('admins')->onDelete('cascade');
            
            $table->string('note');

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
        Schema::dropIfExists('transaction_tickets');
    }
}
