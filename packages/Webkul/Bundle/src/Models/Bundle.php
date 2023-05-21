<?php

namespace Webkul\Bundle\Models;

use Webkul\Area\Models\AreaProxy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Models\ActivityLogProxy;
use Webkul\Product\Models\ProductProxy;
use Webkul\Inventory\Models\InventoryAreaProxy;
use Webkul\Inventory\Models\InventoryWarehouseProxy;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Bundle\Contracts\Bundle as BundleContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Bundle extends Model implements BundleContract, TranslatableContract
{
    use Translatable;

    public const DISCOUNT_TYPE_VALUE        = 'val';
    public const DISCOUNT_TYPE_PERCENT      = 'per';

    public $translatedAttributes = [
        'name', 'description'
    ];

    protected $fillable = ['discount_type', 'discount_value', 'amount', 'start_validity', 'end_validity', 'area_id','status'];

    protected $appends = ['image_url', 'thumb_url'];

    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }
    
    public function areas()
    {
        return $this->belongsToMany(AreaProxy::modelClass(), 'bundle_areas')->withTimestamps();
    }    

    /**
     * The Areas that has this the bundle.
     */
    public function area()
    {
        return $this->belongsTo(AreaProxy::modelClass());
    }
    
    /**
     * the items belongs to the bundle
     */
    public function items()
    {
        return $this->hasMany(BundleItemProxy::modelClass(), 'bundle_id');
    }
    
    /**
     * the product of bundle
     */
    public function product()
    {
        return $this->hasOne(ProductProxy::modelClass(), 'bundle_id');
    }


    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }
        return Storage::url($this->image);
    }

    public function getThumbUrlAttribute()
    {
        if (!$this->thumb) {
            return null;
        }
        return Storage::url($this->thumb);
    }

    /**
     * Scope a query to only include active products.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 1);
    }
}