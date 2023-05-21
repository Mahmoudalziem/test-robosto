<?php

namespace Webkul\Category\Models;

use Webkul\Banner\Models\Banner;
use Webkul\Core\Models\SoldProxy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Models\ActivityLogProxy;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Category\Contracts\SubCategory as SubCategoryContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class SubCategory extends Model implements SubCategoryContract, TranslatableContract {

    use Translatable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sub_categories';
    public $translatedAttributes = [
        'name'
    ];
    protected $hidden = ['pivot'];
    protected $fillable = ['position', 'status'];
    protected $with = ['translations', 'parentCategories'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['image_url', 'thumb_url'];

    /**
     * Get image url for the category image.
     */
    public function getImageUrlAttribute() {
        if (!$this->image)
            return null;

        return Storage::url($this->image);
    }

    public function getThumbUrlAttribute() {
        if (!$this->thumb) {
            return null;
        }
        return Storage::url($this->thumb);
    }

    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    /**
     * Sub Category Belongs To Many Categories
     */
    public function parentCategories() {
        return $this->belongsToMany(CategoryProxy::modelClass(), 'category_sub_categories', 'sub_category_id', 'category_id');
    }

    /**
     * The products that belong to the sub categories.
     */
    public function products() {
        return $this->belongsToMany(ProductProxy::modelClass(), 'product_sub_categories');
    }

    public function banner() {
        return $this->belongsTo(Banner::class, 'action_id', 'id');
    }

    public function solds() {
        return $this->morphMany(SoldProxy::modelClass(), 'soldable');
    }

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
     * Scope a query to Order By position
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePositioned(Builder $query) {
        return $query->orderBy('position');
    }

}
