<?php

namespace Webkul\Supplier\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('suppliers')->delete();
        $now = Carbon::now();

        DB::table('suppliers')->insert([
            [
                'name'     => 'Ahmed mohsen',
                'email'       => 'driver37@robosto.com',
                'work_phone' => '32432432423',
                'mobile_phone' => '32432432423',
                'company_name'     => 'Juhina',
                'address_title'    => 'Address Title',
                'address_city'    => 'Nasr city',
                'address_state'    => 'Cairo',
                'address_zip'    => '62625',
                'address_phone'    => '62625887541',
                'status'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
