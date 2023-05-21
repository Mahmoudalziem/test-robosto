<?php

namespace Webkul\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Promotion\Contracts\PromotionProduct as PromotionProductContract;

class PromotionProduct extends Model implements PromotionProductContract
{
    protected $table = 'promotion_products';

    protected $fillable = ['promotion_id','promotion_apply_id','product_id'];

    /**
     * Sub Category Belongs To Many Categories
     */
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }
}