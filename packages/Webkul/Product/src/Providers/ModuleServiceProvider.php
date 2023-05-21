<?php

namespace Webkul\Product\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Product\Models\Product::class,
        \Webkul\Product\Models\ProductImage::class,
        \Webkul\Product\Models\ProductInventory::class,
        \Webkul\Product\Models\ProductOrderedInventory::class,
        \Webkul\Product\Models\ProductReview::class,
        \Webkul\Product\Models\ProductTag::class,
        \Webkul\Product\Models\ProductTagTranslation::class,        
        \Webkul\Product\Models\Productlabel::class,
        \Webkul\Product\Models\ProductlabelTranslation::class,   

    ];
}