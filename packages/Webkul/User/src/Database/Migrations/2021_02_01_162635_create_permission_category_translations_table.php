<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionCategoryTranslationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('permission_category_translations');        
        Schema::create('permission_category_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('desc')->nullable();
            $table->string('locale')->index();
            $table->integer('permission_category_id')->unsigned();
            $table->foreign('permission_category_id')->references('id')->on('permission_categories')->onDelete('cascade');
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('permission_category_translations');
    }

}
