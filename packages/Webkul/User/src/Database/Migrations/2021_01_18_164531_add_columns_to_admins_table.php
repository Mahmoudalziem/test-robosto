<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToAdminsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('admins', function ($table) {
            $table->string('username')->after('email')->nullable();
            $table->string('id_number')->after('email')->nullable()->unique();
            $table->string('address')->after('email');
            $table->string('image')->after('email')->nullable();
            $table->string('image_id')->after('email')->nullable();
            $table->string('phone_private')->after('email')->nullable();
            $table->string('phone_work')->after('email')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('admins', function (Blueprint $table) {

            $table->dropColumn('username');
            $table->dropColumn('id_number');
            $table->dropColumn('address');
            $table->dropColumn('image');
            $table->dropColumn('image_id');
            $table->dropColumn('phone_private');
            $table->dropColumn('phone_work');
        });
    }

}
