<?php

namespace Webkul\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesTableSeeder extends Seeder
{
    public function run()
    {

        DB::table('countries')->delete();

        DB::table('countries')->insert([
            [
                'code'     => 'eg',
                'name'     => 'Egypt'
            ]
        ]);

        DB::table('country_translations')->delete();
        DB::table('country_translations')->insert([
            [

                'name'             => 'Egypt',
                'country_id'      => 1,
                'locale'           => 'en'
            ],
            [

                'name'             => 'مصر',
                'country_id'      => 1,
                'locale'           => 'ar'
            ]
        ]);
    }
}