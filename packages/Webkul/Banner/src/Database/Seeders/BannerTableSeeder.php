<?php

namespace Webkul\Banner\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BannerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('banners')->delete();
        $now = Carbon::now();

        DB::table('banners')->insert([
            [
                'area_id'   =>  1,
                'name'     => 'Test Banner',
                'start_date'    =>  now(),
                'end_date'     => now()->addDays(10),
                'position'  =>  1,
                'action_id' =>  1,
                'actionable_type'   =>  'actionable_type',
                'section'   =>  'sale',
                'status'  => 1,
                'default'   =>  1,
                "image_ar" => "banners/F9zGTd8moFv4nGwb71YWneAYDvgs3G4C3tgEXeA0.png",
                "image_en" => "banners/F9zGTd8moFv4nGwb71YWneAYDvgs3G4C3tgEXeA0.png",
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
