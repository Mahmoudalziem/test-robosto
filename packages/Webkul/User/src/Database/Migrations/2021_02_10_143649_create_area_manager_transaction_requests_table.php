<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAreaManagerTransactionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('area_manager_transaction_requests', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('area_id')->nullable();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');

            $table->unsignedInteger('area_manager_id')->nullable();
            $table->foreign('area_manager_id')->references('id')->on('admins')->onDelete('cascade');
            
            $table->unsignedInteger('accountant_id')->nullable();
            $table->foreign('accountant_id')->references('id')->on('admins')->onDelete('cascade');

            $table->decimal('amount', 7, 2)->default(0);
            $table->string('transaction_id')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->text('image')->nullable();
            $table->enum('status', ['pending', 'received', 'cancelled',])->default('pending');
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
        Schema::dropIfExists('area_manager_transaction_requests');
    }
}
