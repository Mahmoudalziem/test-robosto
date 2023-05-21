<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Contracts\InventoryProduct as InventoryProductContract;
use Webkul\Product\Models\ProductProxy;

class InventoryProduct extends Model implements InventoryProductContract
{
    protected $fillable = [
        'sku',
        'prod_date',
        'exp_date',
        'qty',
        'cost_before_discount',
        'cost',
        'amount_before_discount',
        'amount',
        'product_id',
        'warehouse_id',
        'area_id'
    ];

    protected $appends = ['price'];

    public function getPriceAttribute()
    {
        return $this->product->price;
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransactionProductProxy::modelClass(), 'inventory_product_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }
    
    public function warehouse()
    {
        return $this->belongsTo(WarehouseProxy::modelClass());
    }
    
    public function newEloquentBuilder($query) {
        return new \Webkul\Core\Eloquent\BaseEloquentBuilder($query);
    }      
}