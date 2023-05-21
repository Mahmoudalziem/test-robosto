<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Models\WarehouseProxy;
use Webkul\Shipping\Contracts\ShippmentTransfer as ShippmentTransferContract;

class ShippmentTransfer extends Model  implements ShippmentTransferContract
{
    protected $table = 'shippment_transfer';
    protected $fillable = ['from_warehouse_id','to_warehouse_id','status','shippment_id'];
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ON_THE_WAY = 'on_the_way';
    public const STATUS_TRANSFERRED = 'transferred';


    public function fromWarehouse()
    {
        return $this->hasOne(WarehouseProxy::modelClass(), 'id', 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->hasOne(WarehouseProxy::modelClass(), 'id', 'to_warehouse_id');
    }
    public function shippment(){
        return $this->belongsTo(ShippmentProxy::modelClass());
    }
}
