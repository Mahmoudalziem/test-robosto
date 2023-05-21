<?php

namespace Webkul\Brand\Database\Seeders;

use Carbon\Carbon;
use Webkul\Brand\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Webkul\Brand\Models\BrandTranslation;


class BrandTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('brands')->delete();
        DB::table('brand_translations')->delete();

        $brands = array(
            array(
                "id" => 1,
                "position" => 1,
                "image" => "brand\/1\/Robosto-2020110513101852595000.png",
                "status" => 1,
                "created_at" => "2020-11-05 13:10:18.0",
                "updated_at" => "2020-11-09 17:21:02.0"
            ),
            array(
                "id" => 2,
                "position" => 1,
                "image" => "brand\/2\/Robosto-2020110513162584941700.jpeg",
                "status" => 1,
                "created_at" => "2020-11-05 13:16:25.0",
                "updated_at" => "2020-11-05 13:16:25.0"
            ),
            array(
                "id" => 3,
                "position" => 1,
                "image" => "brand\/3\/Robosto-2020110515333217460200.jpeg",
                "status" => 1,
                "created_at" => "2020-11-05 15:33:32.0",
                "updated_at" => "2020-11-05 15:33:32.0"
            ),
            array(
                "id" => 4,
                "position" => 1,
                "image" => "brand\/4\/Robosto-2020110515333532575200.png",
                "status" => 1,
                "created_at" => "2020-11-05 15:33:35.0",
                "updated_at" => "2020-11-05 15:33:35.0"
            ),
            array(
                "id" => 5,
                "position" => 1,
                "image" => "brand\/5\/Robosto-2020110515525475232000.png",
                "status" => 1,
                "created_at" => "2020-11-05 15:48:14.0",
                "updated_at" => "2020-11-05 15:52:54.0"
            ),
            array(
                "id" => 6,
                "position" => 1,
                "image" => "brand\/6\/Robosto-2020110515484386203800.png",
                "status" => 1,
                "created_at" => "2020-11-05 15:48:43.0",
                "updated_at" => "2020-11-05 15:48:43.0"
            ),
            array(
                "id" => 7,
                "position" => 1,
                "image" => "brand\/7\/Robosto-2020110516033008558600.jpeg",
                "status" => 1,
                "created_at" => "2020-11-05 16:03:30.0",
                "updated_at" => "2020-11-05 16:03:30.0"
            ),
            array(
                "id" => 8,
                "position" => 1,
                "image" => "brand\/8\/Robosto-2020111110123452943700.png",
                "status" => 1,
                "created_at" => "2020-11-11 10:12:34.0",
                "updated_at" => "2020-11-15 10:48:16.0"
            )
        );
        DB::table('brands')->insert($brands);


        $brand_translations = array(
            array(
                "id" => 1,
                "name" => "المزرعة",
                "locale" => "ar",
                "brand_id" => 1
            ),
            array(
                "id" => 2,
                "name" => "elmazr3a",
                "locale" => "en",
                "brand_id" => 1
            ),
            array(
                "id" => 3,
                "name" => "المراعى",
                "locale" => "ar",
                "brand_id" => 2
            ),
            array(
                "id" => 4,
                "name" => "elmarai",
                "locale" => "en",
                "brand_id" => 2
            ),
            array(
                "id" => 5,
                "name" => "هاريبو",
                "locale" => "ar",
                "brand_id" => 3
            ),
            array(
                "id" => 6,
                "name" => "haribo",
                "locale" => "en",
                "brand_id" => 3
            ),
            array(
                "id" => 7,
                "name" => "أحمد تي",
                "locale" => "ar",
                "brand_id" => 4
            ),
            array(
                "id" => 8,
                "name" => "Ahmad tea ",
                "locale" => "en",
                "brand_id" => 4
            ),
            array(
                "id" => 9,
                "name" => "روبوستو",
                "locale" => "ar",
                "brand_id" => 5
            ),
            array(
                "id" => 10,
                "name" => "ROBOSTO",
                "locale" => "en",
                "brand_id" => 5
            ),
            array(
                "id" => 11,
                "name" => "صن شاين",
                "locale" => "ar",
                "brand_id" => 6
            ),
            array(
                "id" => 12,
                "name" => "Sunshine",
                "locale" => "en",
                "brand_id" => 6
            ),
            array(
                "id" => 13,
                "name" => "بيربو",
                "locale" => "ar",
                "brand_id" => 7
            ),
            array(
                "id" => 14,
                "name" => "BIRBO",
                "locale" => "en",
                "brand_id" => 7
            ),
            array(
                "id" => 15,
                "name" => "زينة",
                "locale" => "ar",
                "brand_id" => 8
            ),
            array(
                "id" => 16,
                "name" => "zeina",
                "locale" => "en",
                "brand_id" => 8
            )
        );

        DB::table('brand_translations')->insert($brand_translations);
    }
}
