<?php

namespace Webkul\Purchase\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Purchase\Contracts\PurchaseOrderProduct as PurchaseOrderProductContract;

class PurchaseOrderProduct extends Model implements PurchaseOrderProductContract
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
        'purchase_order_id',
        'product_id',
        'warehouse_id',
        'area_id'
    ];


    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrderProxy::modelClass(), 'purchase_order_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }
}