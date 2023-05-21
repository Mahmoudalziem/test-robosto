<?php

namespace Webkul\Core\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class TagTableSeeder extends Seeder {

    public function run() {
        DB::table('tags')->delete();
        $tags = array(
            array(
                "id" => 1,
                "name" => "new-user",
                "created_at" => now(),
                "updated_at" => now()
            ),
            array(
                "id" => 2,
                "name" => "first-order",
                "created_at" => now(),
                "updated_at" => now()
            ),
            array(
                "id" => 3,
                "name" => "second-order",
                "created_at" => now(),
                "updated_at" => now()
            ),
            array(
                "id" => 4,
                "name" => "all-users",
                "created_at" => now(),
                "updated_at" => now()
            ),
            array(
                "id" => 5,
                "name" => "promo-survey",
                "created_at" => now(),
                "updated_at" => now()
            )
        );
        DB::table('tags')->insert($tags);
    }

}
