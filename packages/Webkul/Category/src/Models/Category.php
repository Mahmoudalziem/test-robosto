<?php

namespace Webkul\Category\Models;

use Webkul\Banner\Models\Banner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Models\ActivityLogProxy;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Category\Contracts\Category as CategoryContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Webkul\Core\Models\SoldProxy;

class Category extends Model implements CategoryContract, TranslatableContract
{
    use Translatable;

    public $translatedAttributes = [
        'name'
    ];

    protected $fillable = ['position', 'status'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
      protected $appends = ['image_url', 'thumb_url'];

    // protected $with = ['translations'];

    /**
     * Get image url for the category image.
     */
    public function getImageUrlAttribute()
    {
        if (! $this->image)
            return null;

        return Storage::url($this->image);
    }

    public function getThumbUrlAttribute()
    {
        if (! $this->thumb) {
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
     * Category has many sub
     */
    public function subCategories()
    {
        return $this->belongsToMany(SubCategoryProxy::modelClass(), 'category_sub_categories', 'category_id', 'sub_category_id');
    }


    public function banner()
    {
        return $this->belongsTo(Banner::class, 'action_id' ,'id');
    }


    public function solds()
    {
        return $this->morphMany(SoldProxy::modelClass(), 'soldable');
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
    
    
    /**
     * Scope a query to Order By position
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePositioned(Builder $query)
    {
        return $query->orderBy('position');
    }

}