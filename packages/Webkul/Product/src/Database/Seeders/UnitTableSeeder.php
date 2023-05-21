<?php

namespace Webkul\Product\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('units')->delete();
        DB::table('unit_translations')->delete();

        $units = array(
            array(
                "id" => 1,
                "measure" => "Box",
                "created_at" => "2020-11-05 11:57:47.0",
                "updated_at" => "2020-11-05 11:57:47.0"
            ),
            array(
                "id" => 2,
                "measure" => "CM",
                "created_at" => "2020-11-05 11:56:03.0",
                "updated_at" => "2020-11-05 11:56:03.0"
            ),
            array(
                "id" => 3,
                "measure" => "G",
                "created_at" => "2020-11-05 12:07:14.0",
                "updated_at" => "2020-11-05 12:07:14.0"
            ),
            array(
                "id" => 4,
                "measure" => "Kg",
                "created_at" => "2020-11-05 12:09:22.0",
                "updated_at" => "2020-11-05 12:09:22.0"
            ),
            array(
                "id" => 5,
                "measure" => "Mg",
                "created_at" => "2020-11-05 12:16:21.0",
                "updated_at" => "2020-11-05 12:16:21.0"
            ),
            array(
                "id" => 6,
                "measure" => "L",
                "created_at" => "2020-11-05 12:16:21.0",
                "updated_at" => "2020-11-05 12:16:21.0"
            ),
            array(
                "id" => 7,
                "measure" => "Ml",
                "created_at" => "2020-11-05 12:16:21.0",
                "updated_at" => "2020-11-05 12:16:21.0"
            ),
            array(
                "id" => 8,
                "measure" => "PCs",
                "created_at" => "2020-11-05 12:16:21.0",
                "updated_at" => "2020-11-05 12:16:21.0"
            ),
            array(
                "id" => 9,
                "measure" => "Mm",
                "created_at" => "2020-11-05 12:16:22.0",
                "updated_at" => "2020-11-05 12:16:22.0"
            )
        );
        DB::table('units')->insert($units);

        $unit_translations = array(
            array(
                "id" => 19,
                "name" => "صندوق",
                "locale" => "ar",
                "unit_id" => 1
            ),
            array(
                "id" => 20,
                "name" => "Box",
                "locale" => "en",
                "unit_id" => 1
            ),
            array(
                "id" => 21,
                "name" => "سنتيمتر",
                "locale" => "ar",
                "unit_id" => 2
            ),
            array(
                "id" => 22,
                "name" => "Centimeter",
                "locale" => "en",
                "unit_id" => 2
            ),
            array(
                "id" => 23,
                "name" => "جرام",
                "locale" => "ar",
                "unit_id" => 3
            ),
            array(
                "id" => 24,
                "name" => "Gram",
                "locale" => "en",
                "unit_id" => 3
            ),
            array(
                "id" => 25,
                "name" => "كيلوجرام",
                "locale" => "ar",
                "unit_id" => 4
            ),
            array(
                "id" => 26,
                "name" => "Kilogram",
                "locale" => "en",
                "unit_id" => 4
            ),
            array(
                "id" => 27,
                "name" => "مللي جرام",
                "locale" => "ar",
                "unit_id" => 5
            ),
            array(
                "id" => 28,
                "name" => "Milligram",
                "locale" => "en",
                "unit_id" => 5
            ),
            array(
                "id" => 29,
                "name" => "لتر",
                "locale" => "ar",
                "unit_id" => 6
            ),
            array(
                "id" => 30,
                "name" => "Liter",
                "locale" => "en",
                "unit_id" => 6
            ),
            array(
                "id" => 31,
                "name" => "مللي لتر",
                "locale" => "ar",
                "unit_id" => 7
            ),
            array(
                "id" => 32,
                "name" => "MilliLiter",
                "locale" => "en",
                "unit_id" => 7
            ),
            array(
                "id" => 33,
                "name" => "قطعة",
                "locale" => "ar",
                "unit_id" => 8
            ),
            array(
                "id" => 34,
                "name" => "Piece",
                "locale" => "en",
                "unit_id" => 8
            ),
            array(
                "id" => 35,
                "name" => "مللي متر",
                "locale" => "ar",
                "unit_id" => 9
            ),
            array(
                "id" => 36,
                "name" => "MilliMeter",
                "locale" => "en",
                "unit_id" => 9
            )
        );
        DB::table('unit_translations')->insert($unit_translations);
    }
}