<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Models\ActivityLogProxy;
use Webkul\Core\Contracts\Shelve as ShelveContract;

class Shelve extends Model implements ShelveContract
{

    protected $fillable = ['name', 'row', 'position'];

    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    /**
     * Get Shelve Products
     */
    public function products()
    {
        return $this->hasMany(ProductProxy::modelClass(), 'shelve_id');
    }
}