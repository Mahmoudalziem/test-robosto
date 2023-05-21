<?php

namespace Webkul\Area\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AreaOpenHoursTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        Model::unguard();
        $now = Carbon::now();

        DB::table('area_open_hours')->truncate();
        $areas = DB::table('areas')->get();
        foreach ($areas as $area) {
            $areaClosedHours = array(
                array(
                    "area_id" => $area->id,
                    "rank" => 1,
                    "from_day" => "Sunday",
                    "from_hour" => "08:00",
                    "to_day" => "Sunday",
                    "to_hour" => "13:00",
                    "created_at" => $now,
                    "updated_at" => $now
                ),
                array(
                    "area_id" => $area->id,
                    "rank" => 2,
                    "from_day" => "Sunday",
                    "from_hour" => "15:00",
                    "to_day" => "Sunday",
                    "to_hour" => "23:00",
                    "created_at" => $now,
                    "updated_at" => $now
                ),
                array(
                    "area_id" => $area->id,
                    "rank" => 3,
                    "from_day" => "Monday",
                    "from_hour" => "06:00",
                    "to_day" => "Monday",
                    "to_hour" => "11:00",
                    "created_at" => $now,
                    "updated_at" => $now
                ),
                array(
                    "area_id" => $area->id,
                    "rank" => 4,
                    "from_day" => "Monday",
                    "from_hour" => "14:00",
                    "to_day" => "Monday",
                    "to_hour" => "15:00",
                    "created_at" => $now,
                    "updated_at" => $now
                ),
                array(
                    "area_id" => $area->id,
                    "rank" => 5,
                    "from_day" => "Monday",
                    "from_hour" => "18:00",
                    "to_day" => "Tuesday",
                    "to_hour" => "02:00",
                    "created_at" => $now,
                    "updated_at" => $now
                ),
                array(
                    "area_id" => $area->id,
                    "rank" => 6,
                    "from_day" => "Tuesday",
                    "from_hour" => "08:00",
                    "to_day" => "Tuesday",
                    "to_hour" => "11:00",
                    "created_at" => $now,
                    "updated_at" => $now
                ),
                array(
                    "area_id" => $area->id,
                    "rank" => 7,
                    "from_day" => "Tuesday",
                    "from_hour" => "14:00",
                    "to_day" => "Tuesday",
                    "to_hour" => "15:00",
                    "created_at" => $now,
                    "updated_at" => $now
                ),
                array(
                    "area_id" => $area->id,
                    "rank" => 8,
                    "from_day" => "Tuesday",
                    "from_hour" => "17:00",
                    "to_day" => "Wednesday",
                    "to_hour" => "02:00",
                    "created_at" => $now,
                    "updated_at" => $now
                ),
                array(
                    "area_id" => $area->id,
                    "rank" => 9,
                    "from_day" => "Wednesday",
                    "from_hour" => "08:00",
                    "to_day" => "Wednesday",
                    "to_hour" => "22:00",
                    "created_at" => $now,
                    "updated_at" => $now
                ),
                array(
                    "area_id" => $area->id,
                    "rank" => 10,
                    "from_day" => "Thursday",
                    "from_hour" => "02:00",
                    "to_day" => "Thursday",
                    "to_hour" => "04:00",
                    "created_at" => $now,
                    "updated_at" => $now
                ),
                array(
                    "area_id" => $area->id,
                    "rank" => 11,
                    "from_day" => "Thursday",
                    "from_hour" => "08:00",
                    "to_day" => "Thursday",
                    "to_hour" => "16:00",
                    "created_at" => $now,
                    "updated_at" => $now,
                ),
                array(
                    "area_id" => $area->id,
                    "rank" => 12,
                    "from_day" => "Friday",
                    "from_hour" => "08:00",
                    "to_day" => "Friday",
                    "to_hour" => "17:00",
                    "created_at" => $now,
                    "updated_at" => $now,
                ),
            );
            DB::table('area_open_hours')->insert($areaClosedHours);
        }
    }

}
