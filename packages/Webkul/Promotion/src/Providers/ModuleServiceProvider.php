<?php

namespace Webkul\Promotion\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Promotion\Models\Promotion::class,
        \Webkul\Promotion\Models\PromotionApply::class,
        \Webkul\Promotion\Models\PromotionCategory::class,
        \Webkul\Promotion\Models\PromotionSubCategory::class,
        \Webkul\Promotion\Models\PromotionProduct::class,
        \Webkul\Promotion\Models\PromotionRedeem::class,
        \Webkul\Promotion\Models\PromotionException::class,
        \Webkul\Promotion\Models\PromotionVoidDevice::class
    ];
}