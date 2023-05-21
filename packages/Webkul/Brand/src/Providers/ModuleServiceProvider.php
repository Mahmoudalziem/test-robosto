<?php

namespace Webkul\Brand\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Brand\Models\Brand::class,
        \Webkul\Brand\Models\BrandTranslation::class,
    ];
}