<?php

namespace Webkul\Discount\Models;

use Webkul\Area\Models\AreaProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Models\ActivityLogProxy;
use Webkul\Discount\Contracts\Discount as DiscountContract;

class Discount extends Model implements DiscountContract {

    public const DISCOUNT_VAL = 'val';
    public const DISCOUNT_PER = 'per';

    protected $fillable = ['discount_type', 'discount_value', 'area_id', 'product_id','discount_qty', 'orginal_price', 'discount_price', 'start_validity', 'end_validity'];

    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    public function area() {
        return $this->belongsTo(AreaProxy::modelClass() );
    }
    
    public function areas()
    {
        return $this->belongsToMany(AreaProxy::modelClass(), 'discount_areas')->withTimestamps();
    }    

    public function product() {
        return $this->belongsTo(ProductProxy::modelClass());
    }

}
