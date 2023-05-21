<?php

namespace Webkul\Supplier\Models;

use Webkul\Area\Models\AreaProxy;
use Webkul\Brand\Models\BrandProxy;
use Webkul\Core\Models\CountryProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Models\ActivityLogProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Supplier\Models\SupplierProductProxy;
use Webkul\Supplier\Contracts\Supplier as SupplierContract;

class Supplier extends Model implements SupplierContract
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'email',
        'work_phone',
        'mobile_phone',
        'company_name',
        'address_title',
        'address_city',
        'address_state',
        'address_zip',
        'address_phone',
        'address_fax',
        'remarks',
        'status'
    ];

    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    /**
     * The Country that has this the supplier.
     */
    public function country()
    {
        return $this->belongsTo(CountryProxy::modelClass());
    }

    /**
     * the areas that belongs to supplier
     */
    public function areas()
    {
        return $this->belongsToMany(AreaProxy::modelClass(), 'supplier_areas');
    }

    /**
     * Get all products for the supplier
     */
    public function supplierProducts()
    {
        return $this->hasMany(SupplierProductProxy::modelClass(), 'supplier_id');
    }

    /**
     * the brands that belongs to supplier
     */
    public function brands()
    {
        return $this->belongsToMany(BrandProxy::modelClass(), 'supplier_products', 'supplier_id', 'brand_id')->groupBy('brand_id');
    }

    /**
     * Get all products for the supplier
     */
    public function products()
    {
        return $this->belongsToMany(ProductProxy::modelClass(), 'supplier_products', 'supplier_id', 'product_id');
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