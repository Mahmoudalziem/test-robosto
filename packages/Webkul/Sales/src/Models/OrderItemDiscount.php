<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Contracts\OrderItemDiscount as OrderItemDiscountContract;

class OrderItemDiscount extends Model implements OrderItemDiscountContract
{
    protected $table = 'order_items_discounts';

    protected $fillable = [
        'product_id', 'order_id', 'order_item_id', 'qty'
    ];

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
        return $this->belongsTo(OrderItemProxy::modelClass(), 'order_item_id');
    }
}