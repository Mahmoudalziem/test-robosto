<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Driver\Models\DriverProxy;
use Webkul\Product\Models\ProductProxy;
use Webkul\Sales\Contracts\OrderDriverDispatch as OrderDriverDispatchContract;

class OrderDriverDispatch extends Model implements OrderDriverDispatchContract
{

    public const STATUS_NOT_SEND    = 'not_send';
    public const STATUS_PENDING     = 'pending';
    public const STATUS_CANCELLED    = 'cancelled';


    protected $fillable = ['order_id', 'driver_id', 'dispatched_at', 'status', 'reason', 'rank', 'tries'];
    protected $table = 'order_driver_dispatches';

    public function order()
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }
    
    public function driver()
    {
        return $this->belongsTo(DriverProxy::modelClass(), 'driver_id');
    }
}
