<?php

namespace Webkul\Category\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Category\Models\Category::class,
        \Webkul\Category\Models\CategoryTranslation::class,
        \Webkul\Category\Models\SubCategory::class,
        \Webkul\Category\Models\SubCategoryTranslation::class,
    ];
}