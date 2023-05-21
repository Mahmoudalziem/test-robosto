<?php

namespace Webkul\Inventory\Models;

use Carbon\Carbon;
use Webkul\Area\Models\AreaProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Models\ActivityLogProxy;
use Webkul\Inventory\Models\InventoryAdjustmentActionProxy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Inventory\Contracts\InventoryAdjustment as InventoryAdjustmentContract;

class InventoryAdjustment extends Model implements InventoryAdjustmentContract {

    use SoftDeletes;
    
    public const STATUS_CANCELLED = 0;
    public const STATUS_PENDING = 1;
    public const STATUS_APPROVED = 2;    

    protected $fillable = ['warehouse_id', 'area_id', 'status', 'is_inventory_control'];

    /**
     * Get all Logs
     */
    public function logs() {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    public function warehouse() {
        return $this->belongsTo(WarehouseProxy::modelClass(), 'warehouse_id');
    }

    public function areas() {
        return $this->belongsTo(AreaProxy::modelClass(), 'area_id');
    }

    public function adjustmentProducts() {
        return $this->hasMany(InventoryAdjustmentProduct::class, 'inventory_adjustment_id');
    }

    public function actions() {
        return $this->hasMany(InventoryAdjustmentActionProxy::modelClass(), 'inventory_adjustment_id');
    }

    /**
     * Get the order logs record associated with the order.
     */
    public function handleTimeline() {
        $logs = [];
        foreach ($this->actions as $log) {
            $logs[] = [
                'creator_type' => $log->admin_type,                
                'creator' => $log->created_by,
                'action' => $log->action,
                'date' => Carbon::parse($log->created_at)->format('d M Y h:i:s a'),
            ];
        }

        return $logs;
    }

    public function newEloquentBuilder($query) {
        return new \Webkul\Core\Eloquent\BaseEloquentBuilder($query);
    }

}
