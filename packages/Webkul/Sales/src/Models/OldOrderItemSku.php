<?php

namespace Webkul\Sales\Models;

use Webkul\Sales\Models\OrderProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Sales\Models\OldOrderItemProxy;
use Webkul\Sales\Contracts\OldOrderItemSku as OldOrderItemSkuContract;

class OldOrderItemSku extends Model implements OldOrderItemSkuContract
{
    protected $table = 'old_order_items_skus';

    protected $fillable = [
        'product_id', 'order_id', 'old_order_item_id', 'sku', 'qty'
    ];

    public $timestamps = false;

    /**
     * Get the order record associated with the order item.
     */
    public function order()
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }

    /**
     * Get the product record associated with the order item.
     */
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    /**
     * Get the product record associated with the order item.
     */
    public function orderItem()
    {
        return $this->belongsTo(OldOrderItemProxy::modelClass(), 'old_order_item_id');
    }
}