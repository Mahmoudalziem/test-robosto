<?php

namespace Webkul\Bundle\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Bundle\Models\Bundle::class,
        \Webkul\Bundle\Models\BundleItem::class,
        \Webkul\Bundle\Models\BundleTranslation::class
    ];
}
