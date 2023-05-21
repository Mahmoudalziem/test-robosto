<?php

namespace Webkul\Banner\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Webkul\Banner\Models\Banner;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Banner::class
    ];
}