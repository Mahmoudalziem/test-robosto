<?php

namespace Webkul\Supplier\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Supplier\Models\Supplier::class,
        \Webkul\Supplier\Models\SupplierProduct::class,
        \Webkul\Supplier\Models\SupplierArea::class
    ];
}