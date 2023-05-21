<?php

namespace Webkul\Brand\Models;

use Webkul\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Models\ActivityLogProxy;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Supplier\Models\SupplierProxy;
use Webkul\Brand\Contracts\Brand as BrandContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Brand extends Model implements BrandContract, TranslatableContract
{
    use Translatable;

    public $translatedAttributes = [
        'name'
    ];

    protected $fillable = ['position', 'prefix', 'status'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
      protected $appends = ['image_url'];

    /**
     * Get image url for the category image.
     */
    public function getImageUrlAttribute()
    {
        if (! $this->image)
            return null;

        return Storage::url($this->image);
    }

    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    /**
     * Products that belongs to Brand
     */
    public function products()
    {
        return $this->hasMany(ProductProxy::modelClass());
    }

    /**
     * Get all supplier for the supplier product
     */
    public function suppliers()
    {
        return $this->belongsToMany(SupplierProxy::modelClass(), 'supplier_products', 'brand_id', 'supplier_id')->groupBy('supplier_id');
    }


    /**
     * Scope a query to only include active categories.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 1);
    }


}
