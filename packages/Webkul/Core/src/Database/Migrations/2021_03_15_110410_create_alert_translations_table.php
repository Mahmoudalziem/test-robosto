<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlertTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alert_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->text('title')->nullable(); // content notification            
            $table->text('body'); // content notification
            $table->integer('alert_id')->unsigned();
            $table->string('locale');
            $table->unique(['alert_id','locale']);
            $table->timestamps();
            $table->foreign('alert_id')->references('id')->on('alerts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('alert_translations');
    }
}
