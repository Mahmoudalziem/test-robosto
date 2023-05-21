<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBundleItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bundle_items', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('quantity')->default(0);
            $table->decimal('original_price', 12, 2)->default(0)->unsigned();
            $table->decimal('bundle_price', 12, 2)->default(0)->unsigned();
            $table->decimal('total_original_price', 12, 2)->default(0)->unsigned();
            $table->decimal('total_bundle_price', 12, 2)->default(0)->unsigned();

            $table->integer('bundle_id')->unsigned();
            $table->foreign('bundle_id')->references('id')->on('bundles')->onDelete('cascade');
            $table->integer('product_id')->unsigned()->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

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
        Schema::dropIfExists('bundle_items');
    }
}
