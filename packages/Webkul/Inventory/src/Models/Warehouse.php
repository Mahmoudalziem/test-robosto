<?php

namespace Webkul\Inventory\Models;

use Webkul\Area\Models\AreaProxy;
use Webkul\User\Models\AdminProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Collector\Models\CollectorProxy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Driver\Models\DriverTransactionRequestProxy;
use Webkul\Inventory\Contracts\Warehouse as WarehouseContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Warehouse extends Model implements WarehouseContract, TranslatableContract {

    use Translatable, SoftDeletes;


    public $translatedAttributes = [
        'name', 'description'
    ];
    protected $fillable = [
        'contact_name',
        'contact_email',
        'contact_number',
        'address',
        'latitude',
        'longitude',
        'is_main',
        'status',
        'area_id'
    ];
    protected $hidden = ['translations'];

    /**
     * Get the area that has this warehouse
     */
    public function area() {
        return $this->belongsTo(AreaProxy::modelClass());
    }

    public function collectors() {
        return $this->hasMany(CollectorProxy::modelClass());
    }

    public function products() {
        return $this->belongsToMany(ProductProxy::modelClass(), 'inventory_areas', 'warehouse_id', 'product_id');
    }

    public function transactions()
    {
        return $this->hasMany(DriverTransactionRequestProxy::modelClass());
    }

    public function transactionIN() {
        return $this->hasMany(InventoryTransactionProxy::modelClass(), 'to_warehouse_id');
    }

    public function transactionOUT() {
        return $this->hasMany(InventoryTransactionProxy::modelClass(), 'from_warehouse_id');
    }

    public function admins() {
        return $this->belongsToMany(AdminProxy::modelClass(), 'admin_warehouses')->withTimestamps();
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
    
    public function newEloquentBuilder($query) {
        return new \Webkul\Core\Eloquent\BaseEloquentBuilder($query);
    }    

}
