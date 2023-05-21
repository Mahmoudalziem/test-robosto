<?php

namespace Webkul\Driver\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MotorTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('motors')->delete();
        $now = Carbon::now();

        DB::table('motors')->insert([
            [
                'chassis_no'   => '33sssdccs',
                'license_plate_no'      => 'س ت ع ',
                'condition'     => '',
                'image'     => 'first_name',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);

    }
}
