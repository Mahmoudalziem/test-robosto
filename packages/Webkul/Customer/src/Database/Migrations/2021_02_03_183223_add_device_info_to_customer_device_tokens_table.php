<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeviceInfoToCustomerDeviceTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_device_tokens', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('customer_id');
            $table->string('device_type')->nullable()->after('device_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_device_tokens', function (Blueprint $table) {
            $table->dropColumn('device_id');
            $table->dropColumn('device_type');
        });
    }
}
