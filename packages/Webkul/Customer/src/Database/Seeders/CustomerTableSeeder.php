<?php

namespace Webkul\Customer\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Customer\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('customers')->delete();
        $now = date("Y-m-d H:i:s");

        $customers= [
            'channel_id'        => random_int(1,2),
            'avatar'            =>  DB::table('avatars')->get()->random()->id,
            'name'              => "customer 01",
            'gender'            => random_int(0, 1),
            'email'             => "customer01@gmail.com",
            'phone'             => '01151716662',
            'landline'          => '02257716662',
            'status'            => random_int(0, 1),
            'created_at'        => $now,
            'updated_at'        => $now,
        ];

        DB::table('customers')->insert($customers);
    }
}
