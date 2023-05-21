<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Webkul\Customer\Models\Customer;

$factory->define(Customer::class, function (Faker $faker) {
    $now = date("Y-m-d H:i:s");
    $password = $faker->password;

    return [
        'channel_id'        => random_int(1,2),
        'avatar'            =>  DB::table('avatars')->get()->random()->id,
        'name'              => $faker->name,
        'gender'            => random_int(0, 1),
        'email'             => $faker->email,
        'phone'             => '01151715555',
        'landline'          => $faker->unique()->phoneNumber,
        'status'            => random_int(0, 1),
        'created_at'        => $now,
        'updated_at'        => $now,
    ];
});
