<?php

namespace Webkul\Motor\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Motor\Models\Motor::class,
    ];
}