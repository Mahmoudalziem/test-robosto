<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\ActivityLogProxy;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Product\Contracts\ProductTag as ProductTagContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class ProductTag extends Model implements ProductTagContract, TranslatableContract {

    use Translatable;

    protected $table = 'product_tags';    
    protected $fillable = ['status'];
    public $translatedAttributes = [
        'name'
    ];
    
    protected $hidden = ['pivot'];

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
        return $this->belongsToMany(ProductProxy::modelClass(), 'product_tag_related');
    }
    
}
