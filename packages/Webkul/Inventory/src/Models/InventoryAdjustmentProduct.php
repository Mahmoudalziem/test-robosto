<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Models\ProductProxy;
use Webkul\Inventory\Contracts\InventoryAdjustmentProduct as InventoryAdjustmentProductContract;
use Webkul\Purchase\Models\PurchaseOrderProductProxy;

class InventoryAdjustmentProduct extends Model implements InventoryAdjustmentProductContract
{
    
    public const STATUS_LOST = 1;
    public const STATUS_EXPIRED = 2;
    public const STATUS_OVERQTY = 3;  
    public const STATUS_DAMAGED = 4;     
    public const STATUS_RETURN_TO_VENDOR = 5;   
    
    protected $fillable = [

        'inventory_adjustment_id',
        'product_id',
        'sku',
        'qty',
        'qty_stock_before',
        'qty_stock_after',
        'note',
        'status',
    ];
    protected $appends = ['image_url' ];
    
    public function adjustment()
    {
        return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrderProductProxy::modelClass(),'sku','sku');
    } 
    
    public function getImageUrlAttribute()
    {
        if (! $this->image) {
            return null;
        }
        return Storage::url($this->image);
    }

}