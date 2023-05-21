<?php

namespace Webkul\Driver\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Driver\Models\Driver::class,
        \Webkul\Driver\Models\DriverDeviceToken::class,
        \Webkul\Driver\Models\DriverLogLogin::class,
        \Webkul\Driver\Models\DriverLogBreak::class,
        \Webkul\Driver\Models\DriverLogEmergency::class,
        \Webkul\Driver\Models\DriverMotor::class,
        \Webkul\Driver\Models\DriverTransactionRequest::class,
        \Webkul\Driver\Models\DriverRating::class,
        \Webkul\Driver\Models\WorkingCycle::class,
        \Webkul\Driver\Models\WorkingCycleOrder::class,
        \Webkul\Driver\Models\SupervisorRating::class,
        \Webkul\Driver\Models\MonthlyBonus::class
    ];
}