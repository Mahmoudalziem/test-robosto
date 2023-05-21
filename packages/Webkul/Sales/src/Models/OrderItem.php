<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Sales\Contracts\OrderItem as OrderItemContract;
use Webkul\Product\Models\Product;
use Webkul\Bundle\Models\BundleItemProxy;

class OrderItem extends Model implements OrderItemContract
{
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'additional' => 'array',
    ];

    protected $fillable = [
        'product_id','bundle_id', 'order_id', 'shelve_name', 'shelve_position', 
        'weight', 'qty_ordered', 'qty_shipped', 'qty_invoiced', 'price', 
        'base_price', 'total', 'base_total', 'total_invoiced', 'base_total_invoiced',
        'discount_type', 'discount_amount'
    ];

    protected $appends = [

    ];


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
        return $this->hasMany(OrderItemSkuProxy::modelClass(), 'order_item_id');
    }

    /**
     * Get the product record associated with the order item.
     */
    public function item()
    {
        return $this->belongsTo(ProductProxy::modelClass(),'product_id');
    }

    /**
     * Get the invoice items record associated with the order item.
     */
    public function invoice_items()
    {
        return $this->hasMany(InvoiceItemProxy::modelClass());
    }

    public function bundleItems() {
       return $this->hasMany(BundleItemProxy::modelClass(),'bundle_id', 'bundle_id');
    }

}