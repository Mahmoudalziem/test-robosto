<?php

namespace Webkul\Area\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Area\Models\AreaOpenHour;

class AreaClosedHoursTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {


        $weekDays[1] = 'Sunday';
        $weekDays[2] = 'Monday';
        $weekDays[3] = 'Tuesday';
        $weekDays[4] = 'Wednesday';
        $weekDays[5] = 'Thursday';
        $weekDays[6] = 'Friday';
        $weekDays[7] = 'Saturday';
        $now = Carbon::now()->toDateTimeString();
        //DB::table('area_closed_hours')->delete();
        DB::table('area_closed_hours')->truncate();
        $areas = DB::table('areas')->get();
        $AreaClosedHours = [];
        foreach ($areas as $area) {
            // open hours
            $areaOpenHours = AreaOpenHour::where('area_id', $area->id);
            $rankCount = $areaOpenHours->count();
            $areaOpenHoursData = $areaOpenHours->get();
            $rank = 1;
            $listClosedHours = [];
            foreach ($areaOpenHoursData as $openHours) {
                $nextRank = $openHours->rank + 1;
                Log::info(['nextRank' => $nextRank]);
                Log::info(['rank count ' => $rankCount]);
                $closedHour = [];
                if ($openHours->rank != $rankCount) {
                    $nextOpenHour = AreaOpenHour::where('area_id', $area->id)
                                    ->where('rank', $nextRank)->first();
                    $closedHour = [
                        "area_id" => $area->id,
                        "rank" => $openHours->rank,
                        "from_day" => $openHours->to_day,
                        "from_hour" => $openHours->to_hour,
                        "to_day" => $nextOpenHour->from_day,
                        "to_hour" => $nextOpenHour->from_hour,
                        "created_at" => $now,
                        "updated_at" => $now,
                    ];
                } else {
                    $firstOpenHour = AreaOpenHour::where('area_id', $area->id)
                                    ->where('rank', 1)->first();
                    $closedHour = [
                        "area_id" => $area->id,
                        "rank" => $openHours->rank,
                        "from_day" => $openHours->to_day,
                        "from_hour" => $openHours->to_hour,
                        "to_day" => $firstOpenHour->from_day,
                        "to_hour" => $firstOpenHour->from_hour,
                        "created_at" => $now,
                        "updated_at" => $now,
                    ];
                }
                array_push($listClosedHours, $closedHour);
            }
            array_push($AreaClosedHours, $listClosedHours);
        } // foreach area
        Log::info([' listClosedHours ::: ' => $listClosedHours]);
        Log::info([' AreaClosedHours ::: ' => $AreaClosedHours]);
        // build devided Area Open Hour
        $x = 1;
        foreach ($AreaClosedHours as $areaClosedHourData) {
            $areaRank=1;
            foreach ($areaClosedHourData as $node) {
                $closedDays = [];
                $days = [];

                //  if ($node['from_day'] != $node['to_day']) {
                $daysList = [];
                $startDayIndex = array_search($node['from_day'], $weekDays);
                $endDayIndex = array_search($node['to_day'], $weekDays);

                Log::info(['  startDayIndex ' => $startDayIndex]);
                Log::info(['  endDayIndex ' => $endDayIndex]);
                Log::info("----------------------");

                if ($startDayIndex > $endDayIndex) {
                    for ($i = $startDayIndex; $i <= 7; $i++) {
                        Log::info(['  i : ' => $i]);
                        $daysList[$i] = $weekDays[$i];
                    }
                    for ($i = 1; $i <= $endDayIndex; $i++) {
                        Log::info(['  i : ' => $i]);
                        $daysList[$i] = $weekDays[$i];
                    }
                } else {
                    for ($i = $startDayIndex; $i <= $endDayIndex; $i++) {
                        Log::info(['  i : ' => $i]);
                        $daysList[$i] = $weekDays[$i];
                    }
                }
                Log::info('********************  ');
                Log::info([' daysList  ' => $daysList]);
                Log::info("----------------------");
                Log::info("----------------------");
                $lenghtOfDays = count($daysList);
                Log::info(['count of days' => $lenghtOfDays]);

                $closedDays = [];

                if ($lenghtOfDays > 2) { // 3 days or more
                    $listCount = 1;
                    foreach ($daysList as $k => $day) {
                        if ($listCount == 1) {
                            $closedDays[$k] = [
                                "area_id" => $node['area_id'],
                                "rank" => $x,
                                'from_day' => $day,
                                'from_hour' => $node['from_hour'],
                                'to_day' => $day,
                                'to_hour' => "23:59:59",
                            ];
                        } elseif ($listCount == $lenghtOfDays) { // end of day list
                            $closedDays[$k] = [
                                "area_id" => $node['area_id'],
                                "rank" => $x,
                                'from_day' => $day,
                                'from_hour' => "00:00:00",
                                'to_day' => $day,
                                'to_hour' => $node['to_hour'],
                            ];
                        } else {
                            $closedDays[$k] = [
                                "area_id" => $node['area_id'],
                                "rank" => $x,
                                'from_day' => $day,
                                'from_hour' => "00:00:00",
                                'to_day' => $day,
                                'to_hour' => "23:59:59",
                            ];
                        }
                        $listCount++;
                    }
                } elseif ($lenghtOfDays == 2) {
                    $listCount = 1;
                    foreach ($daysList as $k => $day) {
                        if ($listCount == 1) {
                            $closedDays[$k] = [
                                "area_id" => $node['area_id'],
                                "rank" => $x,
                                'from_day' => $day,
                                'from_hour' => $node['from_hour'],
                                'to_day' => $day,
                                'to_hour' => "23:59:59",
                            ];
                        } else { // end of day list
                            $closedDays[$k] = [
                                "area_id" => $node['area_id'],
                                "rank" => $x,
                                'from_day' => $day,
                                'from_hour' => "00:00:00",
                                'to_day' => $day,
                                'to_hour' => $node['to_hour'],
                            ];
                        }
                        $listCount++;
                    }
                } else { // the same say
                    $listCount = 1;
                    foreach ($daysList as $k => $day) {
                        if ($listCount == 1) {
                            $closedDays[$k] = [
                                "area_id" => $node['area_id'],
                                "rank" => $x,
                                'from_day' => $day,
                                'from_hour' => $node['from_hour'],
                                'to_day' => $day,
                                'to_hour' => $node['to_hour'],
                            ];
                        }
                        $listCount++;
                    }
                }
                Log::info($closedDays);

                Log::info(['day index of from_day: ' . $node['from_day'] . '  ' => $startDayIndex]);
                Log::info(['day index of to_day: ' . $node['to_day'] . '  ' => $endDayIndex]);

                $t = 0;
                foreach ($closedDays as $i => $row) {
                    $areaRank = $areaRank + $t;
                    $row['rank'] = $areaRank;
                    $row['created_at'] = $now;
                    $row['updated_at'] = $now;
                    //  Log::info(['i ' => $i, 'rank ' => $rank, 'new rank ' => $row['rank']]);

                    DB::table('area_closed_hours')->insert($row);

                    $t++;
                }
                if (count($closedDays) < 2) {
                    $areaRank++;
                }
            } // end of $listClosedHours 
        } // end foreach AreaClosedHours
    }

}
