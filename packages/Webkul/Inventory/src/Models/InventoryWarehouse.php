<?php

namespace Webkul\Inventory\Models;

use Webkul\Area\Models\AreaProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Inventory\Contracts\InventoryWarehouse as InventoryWarehouseContract;
use Webkul\Bundle\Models\BundleProxy;

class InventoryWarehouse extends Model implements InventoryWarehouseContract
{
    protected $fillable = ['product_id', 'warehouse_id', 'area_id', 'qty', 'can_order','bundle_id'];
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }
    
    public function bundle()
    {
        return $this->belongsTo(BundleProxy::modelClass(), 'bundle_id');
    }     

}