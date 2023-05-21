<?php

namespace Webkul\Promotion\Models;

use Carbon\Carbon;
use Webkul\Core\Models\TagProxy;
use Webkul\Area\Models\AreaProxy;
use Webkul\Product\Models\Product;
use Webkul\Category\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Webkul\Category\Models\SubCategory;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Models\ActivityLogProxy;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Promotion\Contracts\Promotion as PromotionContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Webkul\Promotion\Models\PromotionVoidDeviceProxy;

class Promotion extends Model implements PromotionContract, TranslatableContract {

    use Translatable,
        SoftDeletes;

    public const DISCOUNT_TYPE_VALUE = 'val';
    public const DISCOUNT_TYPE_PERCENT = 'per';
    public const PRICE_APPLIED_ORIGINAL = 'original';
    public const PRICE_APPLIED_DISCOUNTED = 'discounted';
    public const APPLY_TYPE_CATEGORY = 'category';
    public const APPLY_TYPE_SUBCATEGORY = 'subCategory';
    public const APPLY_TYPE_PORDUCT = 'product';
    public const APPLY_TYPE_BOUNDLE = 'boundle';

    public $translatedAttributes = [
        'title', 'description'
    ];
    protected $fillable = [
        'promo_code',
        'discount_type',
        'discount_value',
        'start_validity',
        'end_validity',
        'total_vouchers',
        'usage_vouchers',
        'minimum_order_amount',
        'minimum_items_quantity',
        'total_redeems_allowed',
        'price_applied',
        'apply_type',
        'exceptions_items',
        'send_notifications',
        'is_valid',
        'show_in_app',
        'sms_content',
        'status',
    ];
    protected $casts = [
        'exceptions_items' => 'array',
    ];

    /**
     * Scope a query to only include active categories.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query) {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include active categories.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeShowInApp(Builder $query) {
        return $query->where('show_in_app', 1);
    }

    /**
     * Scope a query to only include valid categories.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeValid(Builder $query) {
        return $query->where('is_valid', 1)
                        ->whereColumn('total_vouchers', '!=', 'usage_vouchers')
                        ->where(function ($q) {
                            $q->where(function ($q) {
                                $q->whereNull('start_validity')
                                ->whereNull('end_validity');
                            })->orWhere(function ($q) {
                                $q->where('start_validity', '<=', now()->toDateTimeString())
                                ->where('end_validity', '>=', now()->toDateTimeString());
                            });
                        });
    }

    /**
     * Get all Logs
     */
    public function logs() {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    public function apply() {
        return $this->hasOne(PromotionApplyProxy::modelClass());
    }

    public function areas() {
        return $this->belongsToMany(AreaProxy::modelClass(), 'promotion_areas')->withTimestamps();
    }

    public function tags() {
        return $this->belongsToMany(TagProxy::modelClass(), 'promotion_tags', 'promotion_id')->withTimestamps();
    }

    public function categories() {
        return $this->hasMany(PromotionCategoryProxy::modelClass());
    }

    public function subcategories() {
        return $this->hasMany(PromotionSubCategoryProxy::modelClass());
    }

    public function products() {
        return $this->hasMany(PromotionProductProxy::modelClass());
    }

    public function exceptionProducts() {
        return $this->hasMany(PromotionExceptionProxy::modelClass());
    }

    public function getPromotionApplyType() {
        $applyType = $this->apply_type;

        if ($applyType == 'category') {
            return $this->categories();
        } elseif ($applyType == 'subCategory') {
            return $this->subcategories();
        } elseif ($applyType == 'product') {
            return $this->products();
        } elseif ($applyType == 'boundl') {
            return $this->products();
        } else {
            return $this->products();
        }
    }

    public function getExceptionItems() {

        $applyType = $this->apply_type;
        if (is_array($this->exceptions_items) && count($this->exceptions_items)) {
            if ($applyType == 'category') {
                return Category::whereIn('id', $this->exceptions_items)->get();
            } elseif ($applyType == 'subCategory') {
                return SubCategory::whereIn('id', $this->exceptions_items)->get();
            } elseif ($applyType == 'product') {
                return Product::whereIn('id', $this->exceptions_items)->get();
            } elseif ($applyType == 'boundl') {
                return Product::whereIn('id', $this->exceptions_items)->get();
            } else {
                return Product::whereIn('id', $this->exceptions_items)->get();
            }
        }
        return null;
    }

    public function customers() {
        return $this->hasMany(PromotionRedeemProxy::modelClass());
    }

    public function voidDevice() {
        return $this->hasMany(PromotionVoidDeviceProxy::modelClass());
    }

}
