<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Contracts\InventoryAdjustmentAction as InventoryAdjustmentActionContract;
use Webkul\User\Models\AdminProxy;
use Webkul\Collector\Models\CollectorProxy;
use Webkul\Inventory\Models\InventoryAdjustmentProxy;

class InventoryAdjustmentAction extends Model implements InventoryAdjustmentActionContract {

    protected $fillable = [
        'inventory_adjustment_id',
        'action', //'pending', 'cancelled', 'approved'        
        'admin_type',
        'admin_id',
    ];
    protected $appends = ['created_by'];

    public function adjustment() {
        return $this->belongsTo(InventoryAdjustmentProxy::modelClass(), 'inventory_adjustment_id');
    }

    public function createdBy() {

        switch ($this->admin_type) {
            case 'admin':
                return $this->belongsTo(AdminProxy::modelClass(), 'admin_id')->first();
                break;
            case 'collector':
                return $this->belongsTo(CollectorProxy::modelClass(), 'admin_id')->first();

                break;

            default:
                break;
        }
    }

    public function getCreatedByAttribute() {

        switch ($this->admin_type) {
            case 'admin':
                return $this->createdBy()->name;

                break;
            case 'collector':
                return $this->createdBy()->name;

                break;

            default:
                break;
        }
    }

}
