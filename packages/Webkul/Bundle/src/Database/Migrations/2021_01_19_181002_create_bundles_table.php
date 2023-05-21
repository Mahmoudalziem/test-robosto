<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBundlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bundles', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('discount_type', ['val', 'per'])->default('val');
            $table->decimal('discount_value')->default(0)->unsigned();
            $table->mediumInteger('amount');
            $table->timestamp('start_validity')->nullable();
            $table->timestamp('end_validity')->nullable();
            $table->text('image')->nullable();
            $table->text('thumb')->nullable();
            $table->decimal('total_original_price', 12, 2)->default(0)->unsigned();
            $table->decimal('total_bundle_price', 12, 2)->default(0)->unsigned();
            $table->boolean('status')->default(1);
            $table->integer('area_id')->unsigned();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
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
        Schema::dropIfExists('bundles');
    }
}
