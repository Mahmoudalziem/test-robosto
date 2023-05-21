<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\ActivityLogProxy;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Product\Contracts\Productlabel as ProductlabelContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Productlabel extends Model implements ProductlabelContract, TranslatableContract {

    use Translatable;

    protected $fillable = ['slug','status'];
    public $translatedAttributes = [
        'name'
    ];

    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }
 

    public function scopeActive(Builder $query) {
        return $query->where('status', 1);
    }

    public function products() {
        return $this->hasMany(ProductProxy::modelClass());
    }

}
