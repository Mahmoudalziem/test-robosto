<?php

namespace Webkul\Purchase\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Purchase\Models\PurchaseOrder::class,
        \Webkul\Purchase\Models\PurchaseOrderProduct::class
    ];
}