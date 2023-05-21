<?php

namespace Webkul\Collector\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CollectorTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('collectors')->delete();
        $now = Carbon::now();

        DB::table('collectors')->insert([
            [
                'area_id'   => 1,
                'warehouse_id'      => 1,
                'name'     => 'Collector First',
                'address'       => 'address',
                'image'  => NULL,
                'phone_work'       => '32432432423',
                'username'       => 'collector01',
                'email'       => 'collector01@robosto.com',
                'password'  => bcrypt('12341234'),
                'status'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
        DB::table('collectors')->insert([
            [
                'area_id'   => 1,
                'warehouse_id'      => 2,
                'name'     => 'Collector Second',
                'address'       => 'address',
                'image'  => NULL,
                'phone_work'       => '32432432423',
                'username'       => 'collector02',
                'email'       => 'collector02@robosto.com',
                'password'  => bcrypt('12341234'),
                'status'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);

    }
}
