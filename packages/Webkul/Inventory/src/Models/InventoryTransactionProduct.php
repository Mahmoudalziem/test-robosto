<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Inventory\Contracts\InventoryTransactionProduct as InventoryTransactionProductContract;
use Webkul\Purchase\Models\PurchaseOrderProductProxy;

class InventoryTransactionProduct extends Model implements InventoryTransactionProductContract
{
    protected $fillable = [
        'qty',
        'inventory_transaction_id',
        'product_id',
        'sku',
        'inventory_product_id'
    ];

    public function transaction()
    {
        return $this->belongsTo(InventoryTransactionProxy::modelClass(), 'inventory_transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    public function inventoryProduct()
    {
        return $this->belongsTo(InventoryProductProxy::modelClass(), 'inventory_product_id');
    }
    
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrderProductProxy::modelClass(),'sku','sku');
    }    
}