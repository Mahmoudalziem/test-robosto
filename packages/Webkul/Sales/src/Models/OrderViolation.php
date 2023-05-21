<?php

namespace Webkul\Sales\Models;

use Webkul\User\Models\AdminProxy;
use Webkul\Sales\Models\OrderProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Collector\Models\CollectorProxy;
use Webkul\Driver\Models\DriverProxy;
use Webkul\Sales\Contracts\OrderViolation as OrderViolationContract;

class OrderViolation extends Model implements OrderViolationContract
{
    public const DRIVER_TYPE = 'driver';
    public const COLLECTOR_TYPE = 'collector';

    protected $fillable = ['type', 'violation_type', 'violation_note', 'order_id', 'admin_id', 'driver_id', 'collector_id'];

    public function order()
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }

    public function admin()
    {
        return $this->belongsTo(AdminProxy::modelClass());
    }

    public function driver()
    {
        return $this->belongsTo(DriverProxy::modelClass());
    }
    
    public function collector()
    {
        return $this->belongsTo(CollectorProxy::modelClass());
    }

}