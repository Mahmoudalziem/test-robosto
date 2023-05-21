<?php

namespace Webkul\Shipping\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Shipping\Models\Shipper::class,
        \Webkul\Shipping\Models\Shippment::class,
        \Webkul\Shipping\Models\PickupLocation::class,
        \Webkul\Shipping\Models\ShippingAddress::class,
        \Webkul\Shipping\Models\ShippmentTransfer::class,
        \Webkul\Shipping\Models\ShippmentLogs::class,
        \Webkul\Shipping\Models\ShippmentBulkTransfer::class,
        \Webkul\Shipping\Models\ShippmentBulkTransferItem::class,
    ];
}