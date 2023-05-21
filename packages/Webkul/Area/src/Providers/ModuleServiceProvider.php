<?php

namespace Webkul\Area\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider {

    protected $models = [
        \Webkul\Area\Models\Area::class,
        \Webkul\Area\Models\AreaTranslation::class,
        \Webkul\Area\Models\AreaOpenHour::class,
        \Webkul\Area\Models\AreaClosedHour::class,
    ];

}
