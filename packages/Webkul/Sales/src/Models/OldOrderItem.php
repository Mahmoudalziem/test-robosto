<?php

namespace Webkul\Sales\Models;

use Webkul\Sales\Models\OrderProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Sales\Models\OldOrderItemSkuProxy;
use Webkul\Sales\Contracts\OldOrderItem as OldOrderItemContract;
use Webkul\Bundle\Models\BundleItemProxy;

class OldOrderItem extends Model implements OldOrderItemContract
{
    protected $table = 'old_order_items';

    protected $casts = [
        'additional' => 'array',
    ];

    protected $fillable = [
        'product_id', 'bundle_id', 'order_id', 'shelve_name', 'shelve_position',
        'weight', 'qty_ordered', 'qty_shipped', 'qty_invoiced', 'price',
        'base_price', 'total', 'base_total', 'total_invoiced', 'base_total_invoiced',
        'discount_type', 'discount_amount'
    ];

    protected $appends = [];


    /**
     * Get the order record associated with the order item.
     */
    public function order()
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }

    /**
     * Get the skus associated with the order item.
     */
    public function skus()
    {
        return $this->hasMany(OldOrderItemSkuProxy::modelClass(), 'old_order_item_id');
    }

    /**
     * Get the product record associated with the order item.
     */
    public function item()
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    public function bundleItems()
    {
        return $this->hasMany(BundleItemProxy::modelClass(), 'bundle_id', 'bundle_id');
    }
}