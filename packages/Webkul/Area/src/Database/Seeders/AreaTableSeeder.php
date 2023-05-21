<?php

namespace Webkul\Area\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AreaTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        DB::table('areas')->delete();
        DB::table('area_translations')->delete();

        $areas = array(
            array(
                "id" => 1,
                "default" => "1",
                "status" => 1,
                "created_at" => "2020-11-05 13:00:53.0",
                "updated_at" => "2020-11-05 13:00:53.0"
            ),
            array(
                "id" => 2,
                "default" => "0",
                "status" => 1,
                "created_at" => "2020-11-05 13:00:53.0",
                "updated_at" => "2020-11-05 13:00:53.0"
            )
        );
        DB::table('areas')->insert($areas);

        $area_translations = array(
            array(
                "id" => 1,
                "name" => "Maadi",
                "area_id" => 1,
                "locale" => "en",
                "created_at" => "2020-11-05 13:00:53.0",
                "updated_at" => "2020-11-05 13:00:53.0"
            ),
            array(
                "id" => 2,
                "name" => "معادي",
                "area_id" => 1,
                "locale" => "ar",
                "created_at" => "2020-11-05 13:00:53.0",
                "updated_at" => "2020-11-05 13:00:53.0"
            ),
            array(
                "id" => 3,
                "name" => "المنصوره",
                "area_id" => 2,
                "locale" => "ar",
                "created_at" => "2020-11-05 13:00:53.0",
                "updated_at" => "2020-11-05 13:00:53.0"
            ),
            array(
                "id" => 4,
                "name" => "Mansoura",
                "area_id" => 2,
                "locale" => "en",
                "created_at" => "2020-11-05 13:00:53.0",
                "updated_at" => "2020-11-05 13:00:53.0"
            )
        );
        DB::table('area_translations')->insert($area_translations);
    }
}
