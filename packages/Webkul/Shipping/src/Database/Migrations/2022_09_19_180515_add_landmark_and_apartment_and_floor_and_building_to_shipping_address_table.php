<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLandmarkAndApartmentAndFloorAndBuildingToShippingAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->string('landmark')->nullable();
            $table->string('apartment_no')->nullable();
            $table->string('building_no')->nullable();
            $table->string('floor_no')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->dropColumn('landmark');
            $table->dropColumn('apartment_no');
            $table->dropColumn('building_no');
            $table->dropColumn('floor_no');
        });
    }
}
