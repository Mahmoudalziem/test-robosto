<?php

namespace Webkul\Customer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AddressIconTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('address_icons')->delete();
        $address_icons = array(
            array(
                "id" => 1,
                "image" => "icons/lms91VeVMP0aBkAWscziP1fXIHQeyR7t6Q7n6hEF.png"
            ),
            array(
                "id" => 2,
                "image" => "icons/4lynJBQyBcBkVjbl6sNYCT3YQ8zkzS99ho0ad7dh.png"
            ),
            array(
                "id" => 3,
                "image" => "icons/QYIenAn8hm8ahPwknUr6qlZTuGFudX0K0NBjXO8U.png"
            ),
            array(
                "id" => 4,
                "image" => "icons/SKOO2sU626HX0yX6V9vuxCUXKA9fHuMGinnCzCTE.png"
            )
        );
        DB::table('address_icons')->insert($address_icons);
    }
}
