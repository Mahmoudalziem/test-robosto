<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Models\WarehouseProxy;
use Webkul\Shipping\Contracts\ShippmentBulkTransfer as ShippmentBulkTransferContract;
use Webkul\User\Models\AdminProxy;

class ShippmentBulkTransfer extends Model  implements ShippmentBulkTransferContract
{
    protected $table = 'shippment_bulk_transfer';
    protected $fillable = ['from_warehouse_id','to_warehouse_id','status','admin_id'];
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

    public function bulkTransferItems(){
        return $this->hasMany(ShippmentBulkTransferItemProxy::modelClass(),'transfer_id','id');
    }

    public function admin() {
        return $this->belongsTo(AdminProxy::modelClass());
    }
}
