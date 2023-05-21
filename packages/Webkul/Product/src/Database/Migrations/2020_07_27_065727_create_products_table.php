<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('barcode')->nullable();
            $table->string('prefix', 2)->nullable();
            $table->text('image')->nullable();
            $table->text('thumb')->nullable();
            $table->boolean('featured')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('returnable')->nullable();

            $table->decimal('price', 7, 2)->nullable();
            $table->decimal('cost', 7, 2)->nullable();
            $table->tinyInteger('tax')->nullable();

            $table->decimal('weight', 7, 2)->nullable();
            $table->decimal('width', 6, 2)->nullable();
            $table->decimal('height', 6, 2)->nullable();
            $table->decimal('length', 6, 2)->nullable();
            $table->integer('sold_count')->unsigned()->default(0);// product sold count
            $table->integer('visits_count')->unsigned()->default(0);// customer visits count
            $table->integer('shelve_id')->unsigned()->nullable();
            $table->foreign('shelve_id')->references('id')->on('shelves')->onDelete('cascade');

            $table->integer('brand_id')->unsigned();
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');

            $table->integer('unit_id')->unsigned();
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->string('unit_value')->nullable();

            $table->timestamps();
        });


        Schema::create('product_sub_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->unsigned();
            $table->integer('sub_category_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('sub_category_id')->references('id')->on('sub_categories')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_sub_categories');

        Schema::dropIfExists('products');
    }
}
