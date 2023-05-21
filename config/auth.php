<?php

return [
    'defaults' => [
        'guard' => 'customer',
        'passwords' => 'customers',
    ],

    'guards' => [
        'web' =>[
            'driver' => 'session',
            'provider' => 'customers'
        ],
        'customer' =>[
            'driver' => 'jwt',
            'provider' => 'customers'
        ],

        'driver' => [ // The driver on google map
            'driver' => 'jwt',
            'provider' => 'drivers',
        ],

        'collector' => [ // The driver on google map
            'driver' => 'jwt',
            'provider' => 'collectors',
        ],
        'admin' => [
            'driver' => 'jwt',
            'provider' => 'admins',
        ],
        'shipper' => [
            'driver' => 'jwt',
            'provider' => 'shippers',
        ],
    ],

    'providers' => [
        'customers' => [
            'driver' => 'eloquent',
            'model' => Webkul\Customer\Models\Customer::class,
        ],

        'drivers' => [
            'driver' => 'eloquent',
            'model' => Webkul\Driver\Models\Driver::class,
        ],

        'collectors' => [
            'driver' => 'eloquent',
            'model' => Webkul\Collector\Models\Collector::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model' => Webkul\User\Models\Admin::class,
        ],
        'shippers' => [
            'driver' => 'eloquent',
            'model' => Webkul\Shipping\Models\Shipper::class,
        ],
    ],

    'passwords' => [
        'admins' => [
            'provider' => 'admins',
            'table' => 'admin_password_resets',
            'expire' => 60,
        ],
        'customers' => [
            'provider' => 'customers',
            'table' => 'customer_password_resets',
            'expire' => 60,
        ],
        'drivers' => [
            'provider' => 'drivers',
            'table' => 'drivers_password_resets',
            'expire' => 60,
        ],
        'shippers' => [
            'provider' => 'shipper',
            'table' => 'shippers_password_resets',
            'expire' => 1000,
        ],
    ],
];