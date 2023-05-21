<?php

namespace Webkul\Customer\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CustomerAddressTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       factory(\Webkul\Customer\Models\CustomerAddress::class, 4)->create();
    }
}
