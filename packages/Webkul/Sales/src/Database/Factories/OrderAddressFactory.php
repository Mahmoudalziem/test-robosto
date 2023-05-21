<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerAddress;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;

$factory->define(OrderAddress::class, function (Faker $faker) {
    $customer = factory(Customer::class)->create();
    $customerAddress = factory(CustomerAddress::class)->make();

    return [
        'first_name'   => $customer->first_name,
        'last_name'    => $customer->last_name,
        'email'        => $customer->email,
        'address1'     => $customerAddress->address1,
        'state'        => $customerAddress->state,
        'city'         => $customerAddress->city,
        'postcode'     => $customerAddress->postcode,
        'phone'        => $customerAddress->phone,
        'order_id'     => function () {
            return factory(Order::class)->create()->id;
        },
    ];
});
