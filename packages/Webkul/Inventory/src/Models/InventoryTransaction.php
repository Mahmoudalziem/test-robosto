<?php

namespace Webkul\Inventory\Models;

use Webkul\Area\Models\AreaProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Models\ActivityLogProxy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Inventory\Contracts\InventoryTransaction as InventoryTransactionContract;
use Webkul\User\Models\AdminProxy;

class InventoryTransaction extends Model implements InventoryTransactionContract 
{
    use SoftDeletes;

    public const STATUS_CANCELLED = 0;
    public const STATUS_PENDING = 1;
    public const STATUS_ON_THE_WAY = 2;
    public const STATUS_TRANSFERRED = 3;

    protected $fillable = ['from_warehouse_id', 'to_warehouse_id','from_area_id','to_area_id', 'status','admin_id', 'transaction_type'];
    protected $appends = ['status_name'];

    /**
     * Get all Logs
     */
    public function logs()
    {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    public function fromWarehouse() {
        return $this->belongsTo(WarehouseProxy::modelClass(), 'from_warehouse_id');
    }

    public function toWarehouse() {
        return $this->belongsTo(WarehouseProxy::modelClass(), 'to_warehouse_id');
    }

    public function fromArea() {
        return $this->belongsTo(AreaProxy::modelClass(), 'from_area_id');
    }

    public function toArea() {
        return $this->belongsTo(AreaProxy::modelClass(), 'to_area_id');
    }
    
    public function transactionProducts() {
        return $this->hasMany(InventoryTransactionProductProxy::modelClass());
    }
    

    public function createdBy() {
        return $this->belongsTo(AdminProxy::modelClass(),'admin_id');
    }      

    public function getStatusNameAttribute() {
        $status = '';
        switch ($this->status) {
            case self::STATUS_CANCELLED:
                $status = 'Cancelled';
                break;
            case self::STATUS_PENDING:
                $status = 'Pending';
                break;
            case self::STATUS_TRANSFERRED:
                $status = 'Transferred';
                break;
            case self::STATUS_ON_THE_WAY:
                $status = 'On tha way';
                break;
        }
        return $status;
    }

    public function newEloquentBuilder($query) {
        return new \Webkul\Core\Eloquent\BaseEloquentBuilder($query);
    }

}
