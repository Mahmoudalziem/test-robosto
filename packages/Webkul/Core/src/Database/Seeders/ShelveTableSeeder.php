<?php

namespace Webkul\Core\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class ShelveTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('shelves')->delete();
        $shelves = array(
            array(
                "id" => 1,
                "name" => "A",
                "position" => 1,
                "row" => 1,
                "created_at" => now(),
                "updated_at" => now()
            )
        );
        DB::table('shelves')->insert($shelves);
    }
}
