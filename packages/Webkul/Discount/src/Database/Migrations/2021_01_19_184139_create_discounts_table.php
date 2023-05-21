<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('discounts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('area_id');            
            $table->unsignedInteger('product_id');
            $table->enum('discount_type', ['val', 'per'])->default('val'); // val = value , per = percent
            $table->integer('discount_value')->default(0);            
            $table->integer('discount_qty')->default(0);               
            $table->decimal('orginal_price', 12, 2)->nullable();
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->timestamp('start_validity');
            $table->timestamp('end_validity');
            $table->boolean('status')->default(0);
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('discounts');
    }

}
