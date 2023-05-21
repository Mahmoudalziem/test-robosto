<?php

namespace Webkul\Collector\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Webkul\Collector\Models\Collector;
use Webkul\Collector\Models\CollectorDeviceToken;
use Webkul\Collector\Models\CollectorLogLogin;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Collector::class,
        CollectorDeviceToken::class,
        CollectorLogLogin::class,
    ];
}