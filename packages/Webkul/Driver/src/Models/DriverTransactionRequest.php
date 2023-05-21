<?php

namespace Webkul\Driver\Models;

use Webkul\Area\Models\AreaProxy;
use Webkul\User\Models\AdminProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Models\WarehouseProxy;
use Webkul\Driver\Contracts\DriverTransactionRequest as DriverTransactionRequestContract;

class DriverTransactionRequest extends Model implements DriverTransactionRequestContract
{

    public const STATUS_PENDING           = 'pending';
    public const STATUS_RECEIVED           = 'received';
    public const STATUS_CANCELLED          = 'cancelled';

    protected $table = 'driver_transaction_requests';

    protected $fillable = ['area_id', 'warehouse_id', 'admin_id', 'driver_id', 'amount', 'current_wallet'];

    public function area()
    {
        return $this->belongsTo(AreaProxy::modelClass());
    }

    public function warehouse()
    {
        return $this->belongsTo(WarehouseProxy::modelClass());
    }

    public function admin()
    {
        return $this->belongsTo(AdminProxy::modelClass());
    }

    public function driver()
    {
        return $this->belongsTo(DriverProxy::modelClass());
    }
}