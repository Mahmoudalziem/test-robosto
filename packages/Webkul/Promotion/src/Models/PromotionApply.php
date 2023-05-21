<?php

namespace Webkul\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Promotion\Contracts\PromotionApply as PromotionApplyContract;

class PromotionApply extends Model implements PromotionApplyContract
{

    public const APPLY_TYPE_CATEGORY        = 'cateogry';
    public const APPLY_TYPE_SUBCATEGORY     = 'subCategory';
    public const APPLY_TYPE_PORDUCT         = 'product';
    public const APPLY_TYPE_BOUNDLE         = 'boundle';

    protected $table = 'promotion_applies';

    protected $fillable = [
        'promotion_id',
        'apply_type',
        'model_type'
    ];

    public function categories(){
        return $this->hasMany(PromotionCategoryProxy::modelClass() );
    }

    public function subcategories(){
        return $this->hasMany(PromotionSubCategoryProxy::modelClass(),'promotion_apply_id');
    }

    public function products(){
        return $this->hasMany(PromotionProductProxy::modelClass(),'promotion_apply_id');
    }
}