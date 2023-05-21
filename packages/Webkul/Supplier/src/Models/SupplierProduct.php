<?php

namespace Webkul\Supplier\Models;

use Webkul\Brand\Models\BrandProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Supplier\Models\SupplierProxy;
use Webkul\Supplier\Contracts\SupplierProduct as SupplierProductContract;

class SupplierProduct extends Model implements SupplierProductContract
{
    protected $fillable = ['supplier_id', 'brand_id', 'product_id'];


    /**
     * The Suuplier that has this the supplier.
     */
    public function supplier()
    {
        return $this->belongsTo(SupplierProxy::modelClass());
    }
    
    /**
     * The Productthat has this the supplier.
     */
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }
    
    /**
     * The Brand that has this the supplier.
     */
    public function brand()
    {
        return $this->belongsTo(BrandProxy::modelClass());
    }
}