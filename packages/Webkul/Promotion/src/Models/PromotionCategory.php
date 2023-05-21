<?php

namespace Webkul\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Category\Models\CategoryProxy;
use Webkul\Promotion\Contracts\PromotionCategory as PromotionCategoryContract;

class PromotionCategory extends Model implements PromotionCategoryContract
{
    protected $table = 'promotion_categories';

    protected $fillable = ['promotion_id','promotion_apply_id','category_id'];

    /**
     * Sub Category Belongs To Many Categories
     */
    public function category()
    {
        return $this->belongsTo(CategoryProxy::modelClass(), 'category_id');
    }
}