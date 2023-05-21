<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDistanceColumnToAreasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->unsignedTinyInteger('min_distance_between_orders')->default(2)->after('main_area_id');
            $table->unsignedTinyInteger('drivers_on_the_way')->default(0)->after('min_distance_between_orders');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn('min_distance_between_orders');
            $table->dropColumn('drivers_on_the_way');
        });
    }
}
