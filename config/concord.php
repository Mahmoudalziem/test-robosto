<?php

return [
    'modules' => [
        /**
         * Example:
         * VendorA\ModuleX\Providers\ModuleServiceProvider::class,
         * VendorB\ModuleY\Providers\ModuleServiceProvider::class
         *
         */

        \Webkul\Brand\Providers\ModuleServiceProvider::class,
        \Webkul\Category\Providers\ModuleServiceProvider::class,
        \Webkul\Core\Providers\ModuleServiceProvider::class,
        \Webkul\Customer\Providers\ModuleServiceProvider::class,
        \Webkul\Inventory\Providers\ModuleServiceProvider::class,
        \Webkul\Product\Providers\ModuleServiceProvider::class,
        \Webkul\Sales\Providers\ModuleServiceProvider::class,
        \Webkul\User\Providers\ModuleServiceProvider::class,
        \Webkul\Area\Providers\ModuleServiceProvider::class,
        \Webkul\Driver\Providers\ModuleServiceProvider::class,
        \Webkul\Motor\Providers\ModuleServiceProvider::class,
        \Webkul\Supplier\Providers\ModuleServiceProvider::class,
        \Webkul\Purchase\Providers\ModuleServiceProvider::class,
        \Webkul\Collector\Providers\ModuleServiceProvider::class,
        \Webkul\Banner\Providers\ModuleServiceProvider::class,
        \Webkul\Promotion\Providers\ModuleServiceProvider::class,
        \Webkul\Bundle\Providers\ModuleServiceProvider::class,
        \Webkul\Discount\Providers\ModuleServiceProvider::class,
        \Webkul\Shipping\Providers\ModuleServiceProvider::class,

    ]
];