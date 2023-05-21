<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('channel_id')->unsigned();
            $table->unsignedInteger('avatar_id')->unsigned()->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->tinyInteger('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone',20)->unique();
            $table->string('landline',20)->nullable();
            $table->string('avatar')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('wallet', 7, 2)->default(0);
            $table->tinyInteger('is_flagged')->nullable();
            $table->boolean('otp_verified')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->boolean('is_online')->default(0)->nullable(); // online / offline
            $table->string('referral_code',50)->nullable();
            $table->boolean('invitation_applied')->default(1);
            $table->unsignedInteger('invited_by')->unsigned()->nullable();
            $table->foreign('invited_by')->references('id')->on('customers')->onDelete('restrict');

            $table->boolean('subscribed_to_news_letter')->default(0);
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('restrict');
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
        Schema::dropIfExists('customers');
    }
}
