<?php

namespace Webkul\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Promotion\Contracts\PromotionException as PromotionExceptionContract;

class PromotionException extends Model implements PromotionExceptionContract
{
    public $timestamps = false;

    protected $fillable = ['promotion_id', 'product_id'];

    public function promotion()
    {
        return $this->belongsTo(PromotionProxy::modelClass(), 'promotion_id');
    }
    
    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }
}