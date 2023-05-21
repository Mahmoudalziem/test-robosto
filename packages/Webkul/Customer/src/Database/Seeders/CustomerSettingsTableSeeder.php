<?php

namespace Webkul\Customer\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Webkul\Customer\Models\Customer;

class CustomerSettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('customer_settings')->delete();

        $now = Carbon::now();
        $customer=Customer::latest()->first();
        DB::table('customer_settings')->insert([
            'customer_id'=>$customer->id,
            'key'         => 'lang',
            'value'        => 'ar',
            'group'        => 'lang',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        DB::table('customer_settings')->insert([
            'customer_id'=>$customer->id,
            'key'         => 'app_notification',
            'value'        => true,
            'group'        => 'notification',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        DB::table('customer_settings')->insert([
            'customer_id'=>$customer->id,
            'key'         => 'email_notification',
            'value'        => true,
            'group'        => 'notification',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        DB::table('customer_settings')->insert([
            'customer_id'=>$customer->id,
            'key'         => 'sms_notification',
            'value'        => true,
            'group'        => 'notification',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

    }
}
