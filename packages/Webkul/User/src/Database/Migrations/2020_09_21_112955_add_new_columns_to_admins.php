<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToAdmins extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('admins', function ($table) {
            Schema::table('admins', function ($table) {
                $table->boolean('is_verified')
                        ->after('password')
                        ->nullable()
                        ->default(0);
            });
            $table
                    ->string('otp', 32)
                    ->after('password')
                    ->unique()
                    ->nullable()
                    ->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('otp');
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('is_verified');
        });
    }

}
