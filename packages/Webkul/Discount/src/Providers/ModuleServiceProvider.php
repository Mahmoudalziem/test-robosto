<?php

namespace Webkul\Discount\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider {

    protected $models = [
        \Webkul\Discount\Models\Discount::class,
    ];

}
