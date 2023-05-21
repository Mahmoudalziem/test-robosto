<?php

namespace Webkul\Driver\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DriverTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('drivers')->delete();
        $now = Carbon::now();

        DB::table('drivers')->insert([
            [
                'area_id'   => 1,
                'warehouse_id'      => 1,
                'name'     => 'Ahmed mohsen',
                'address'       => 'address',
                'image'  => NULL,
                'phone_work' => '32432432423',
                'email'       => 'driver37@robosto.com',
                'username'    => 'driver37',
                'password'  => bcrypt('12341234'),
                'is_online'  => 0,
                'availability'  =>'idle',
                'status'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);

        DB::table('drivers')->insert([
            [
                'area_id'   => 1,
                'warehouse_id'      => 2,
                'name'     => 'Ahmed hedewy',
                'address'       => 'address',
                'image'  => NULL,
                'phone_work'       => '32432432423',
                'email'       => 'driver38@robosto.com',
                'username'       => 'driver38',
                'password'  => bcrypt('12341234'),
                'is_online'  => 0,
                'availability'  =>'idle',
                'status'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
