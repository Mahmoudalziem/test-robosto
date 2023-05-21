<?php

namespace Webkul\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Category\Models\SubCategory;
use Webkul\Category\Models\SubCategoryProxy;
use Webkul\Product\Models\ProductProxy;
use Webkul\Promotion\Contracts\PromotionSubCategory as PromotionSubCategoryContract;

class PromotionSubCategory extends Model implements PromotionSubCategoryContract
{
    protected $table = 'promotion_sub_categories';

    protected $fillable = ['promotion_id','promotion_apply_id','sub_category_id'];

    public function subCategory()
    {
        return $this->belongsTo(SubCategoryProxy::modelClass(), 'sub_category_id');
    }
}