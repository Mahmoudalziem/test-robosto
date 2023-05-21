<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Area\Models\AreaProxy;
use Webkul\Product\Models\ProductProxy;
use Webkul\Inventory\Contracts\InventoryArea as InventoryAreaContract;
use Webkul\Bundle\Models\BundleProxy;

class InventoryArea extends Model implements InventoryAreaContract
{
    protected $fillable = ['product_id', 'area_id', 'init_total_qty', 'total_qty','bundle_id'];


    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }
    
    public function area()
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }    
    
    public function bundle()
    {
        return $this->belongsTo(BundleProxy::modelClass(), 'bundle_id');
    }    
    
    public function newEloquentBuilder($query) {
        return new \Webkul\Core\Eloquent\BaseEloquentBuilder($query);
    }     
}