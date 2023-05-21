<?php

namespace Webkul\Core\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class ChannelTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('channels')->delete();
        $channels = array(
            array(
                "id" => 1,
                "name" => "Callcenter",
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 2,
                "name" => "MobileApp",
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 3,
                "name" => "ShippingSystem",
                "created_at" => null,
                "updated_at" => null
            )
        );
        DB::table('channels')->insert($channels);

    }
}