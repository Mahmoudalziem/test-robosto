<?php

namespace Webkul\Core\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class LocalesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('channels')->delete();

        DB::table('locales')->delete();

        DB::table('locales')->insert([
            [
                'id'   => 1,
                'code' => 'en',
                'name' => 'English',
                'default'   =>  '0',
                'direction' =>  'ltr'
            ], [
                'id'   => 2,
                'code' => 'ar',
                'name' => 'Arabic',
                'default'   =>  '1',
                'direction' =>  'rtl'
            ]
        ]);
    }
}
