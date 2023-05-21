<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Astrotomic\Translatable\Translatable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Webkul\Product\Contracts\Unit as UnitContract;

class Unit extends Model implements UnitContract, TranslatableContract
{
    use Translatable;

    public $translatedAttributes = [
        'name'
    ];

    protected $fillable = [
        'measure', 'status'
    ];

    public function products()
    {
        return $this->hasMany(ProductProxy::modelClass(),'unit_id');
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