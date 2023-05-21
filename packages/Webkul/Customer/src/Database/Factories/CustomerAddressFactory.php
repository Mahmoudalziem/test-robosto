<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Illuminate\Support\Arr;
use Webkul\Area\Models\Area;
use Faker\Generator as Faker;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerAddress;

$factory->define(CustomerAddress::class, function (Faker $faker) {
    
    return [
        'name'            => $faker->company,
        'customer_id'     => Customer::all()->random()->id,
        'area_id'         => Area::all()->random()->id,
        'building_no'     => $faker->buildingNumber,
        'floor_no'        => $faker->buildingNumber,
        'apartment_no'    => $faker->buildingNumber,
        'landmark'        => $faker->company,
        'latitude'        => '29.96663930190129',
        'longitude'       => '31.254210945617025',
        'address'         => $faker->address,
        'phone'           => $faker->phoneNumber,
    ];
});



