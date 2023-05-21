<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('promo_code')->nullable(); // promo code

            $table->enum('discount_type',['val','per'])->default('val'); // val = value , per = percent
            $table->integer('discount_value')->default(0);
            $table->timestamp('start_validity')->nullable();
            $table->timestamp('end_validity')->nullable();
            $table->unsignedInteger('total_vouchers')->default(0);
            $table->unsignedInteger('usage_vouchers')->default(0);
            $table->decimal('minimum_order_amount',12,2)->nullable();
            $table->unsignedInteger('minimum_items_quantity')->nullable();
            $table->unsignedInteger('total_redeems_allowed')->nullable(); // per users (0 unlimited

            $table->enum('price_applied',['original','discounted'])->default('original');
            $table->enum('apply_type',['category','subCategory','product','boundle'])->default('category')->nullable();
            $table->json('exceptions_items')->nullable();
            $table->boolean('send_notifications')->default(0); // 0,1
            $table->boolean('status')->default(1); // 0,1
            $table->boolean('is_valid')->default(1);
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
        Schema::dropIfExists('promotions');
    }
}
