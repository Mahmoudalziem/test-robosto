<?php

namespace Webkul\Driver\Models;

use Webkul\Area\Models\AreaProxy;
use Webkul\Driver\Models\DriverProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Models\WarehouseProxy;
use Webkul\Driver\Contracts\WorkingCycle as WorkingCycleContract;

class WorkingCycle extends Model implements WorkingCycleContract
{
    protected $table = 'working_cycles';

    public const ACTIVE_STATUS = 'active';
    public const DONE_STATUS = 'done';

    protected $fillable = ['status', 'expected_from', 'expected_to', 'expected_back', 'expected_back_distance', 'expected_time','actual_from', 'actual_to', 'actual_back', 'actual_time', 'target', 'distance', 'driver_id', 'area_id', 'warehouse_id'];

    protected $casts = [
        'path' => 'array'
    ];

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
    
    public function orders()
    {
        return $this->hasMany(WorkingCycleOrderProxy::modelClass(), 'working_cycle_id');
    }
}