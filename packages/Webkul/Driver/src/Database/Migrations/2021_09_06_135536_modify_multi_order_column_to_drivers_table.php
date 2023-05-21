<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\StringType;

class ModifyMultiOrderColumnToDriversTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {

        DB::statement("ALTER TABLE drivers  MODIFY COLUMN multi_order ENUM('1', '0') NOT NULL DEFAULT '1'");
        DB::statement("UPDATE drivers  SET multi_order = '1' ");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('drivers', function (Blueprint $table) {
            //$table->dropColumn('multi_order');
        });
    }

}
