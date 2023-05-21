<?php

namespace Webkul\Core\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->delete();

        $now = Carbon::now();

        DB::table('settings')->insert([
            'key'         => 'driver_support_phone',
            'value'        => '01223695846',
            'icon'      =>   null,
            'group'        => 'driver',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        DB::table('settings')->insert([
            'key'         => 'customer_support_phone',
            'value'        => '01113695846',
            'icon'      =>   null,
            'group'        => 'customer',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        DB::table('settings')->insert([
            'key'         => 'callcenter_phone',
            'value'        => '01113695846',
            'icon'      =>   null,
            'group'        => 'customer',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        DB::table('settings')->insert([
            'key'         => 'website',
            'value'        => 'www.robostodelivery.com',
            'icon'      =>   null,
            'group'        => 'social',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        DB::table('settings')->insert([
            'key'         => 'email_ask',
            'value'        => 'ask@robosto.com',
            'icon'      =>   null,
            'group'        => 'app',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        DB::table('settings')->insert([
            'key'         => 'social.facebook.url',
            'value'        => 'facebook.com',
            'icon' => 'facebook-with-circle',
            'group'        => 'social',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);



        DB::table('settings')->insert([
            'key'         => 'social.twitter.url',
            'value'        => 'twitter.com',
            'icon'      =>   'twitter-with-circle',
            'group'        => 'social',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);


        DB::table('settings')->insert([
            'key'         => 'social.instagram.url',
            'value'        => 'instagram.com',
            'icon'      =>   'instagram-with-circle',
            'group'        => 'social',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);


    }
}
