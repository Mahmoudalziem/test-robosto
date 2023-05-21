<?php

namespace Webkul\Driver\Models;

use Webkul\Area\Models\AreaProxy;
use Webkul\Sales\Models\OrderProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Models\WarehouseProxy;
use Webkul\Driver\Contracts\WorkingCycleOrder as WorkingCycleOrderContract;

class WorkingCycleOrder extends Model implements WorkingCycleOrderContract
{
    protected $table = 'working_cycles_orders';

    protected $fillable = [
        'expected_from', 'expected_to', 'expected_time', 'actual_from', 'actual_to', 'actual_time', 'target', 
        'distance', 'rank', 'working_cycle_id', 'driver_id', 'order_id', 'area_id', 'warehouse_id'];

    public function cycle()
    {
        return $this->belongsTo(WorkingCycleProxy::modelClass(), 'working_cycle_id');
    }
    
    public function driver()
    {
        return $this->belongsTo(DriverProxy::modelClass());
    }

    public function order()
    {
        return $this->belongsTo(OrderProxy::modelClass());
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