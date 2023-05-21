<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Sales\Contracts\OrderItemSku as OrderItemSkuContract;

class OrderItemSku extends Model implements OrderItemSkuContract
{
    protected $table = 'order_item_skus';

    protected $fillable = [
        'product_id', 'order_id', 'order_item_id', 'sku', 'qty'
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
        return $this->belongsTo(OrderItemProxy::modelClass(),'order_item_id');
    }
}