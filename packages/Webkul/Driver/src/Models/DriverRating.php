<?php

namespace Webkul\Driver\Models;

use Webkul\Area\Models\AreaProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Models\WarehouseProxy;
use Webkul\Driver\Contracts\DriverRating as DriverRatingContract;

class DriverRating extends Model implements DriverRatingContract
{
    protected $table = 'driver_ratings';

    protected $fillable = ['month', 'year', 'amount', 'driver_id', 'area_id', 'warehouse_id'];

    public function driver()
    {
        return $this->belongsTo(DriverProxy::modelClass());
    }
    
    public function area()
    {
        return $this->belongsTo(AreaProxy::modelClass());
    }
    
    public function warehouse()
    {
        return $this->belongsTo(WarehouseProxy::modelClass());
    }
}