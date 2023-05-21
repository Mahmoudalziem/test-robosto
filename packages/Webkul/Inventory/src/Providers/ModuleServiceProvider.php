<?php

namespace Webkul\Inventory\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Inventory\Models\InventorySource::class,
        \Webkul\Inventory\Models\Warehouse::class,
        \Webkul\Inventory\Models\InventoryArea::class,
        \Webkul\Inventory\Models\InventoryWarehouse::class,
        \Webkul\Inventory\Models\InventoryProduct::class,
        \Webkul\Inventory\Models\InventoryTransaction::class,
        \Webkul\Inventory\Models\InventoryTransactionProduct::class,
        \Webkul\Inventory\Models\InventoryAdjustment::class,
        \Webkul\Inventory\Models\InventoryAdjustmentProduct::class,  
        \Webkul\Inventory\Models\InventoryAdjustmentAction::class,         
        \Webkul\Inventory\Models\InventoryStockValue::class,        
        \Webkul\Inventory\Models\InventoryControl::class,
        \Webkul\Inventory\Models\ProductStock::class,
    ];
}