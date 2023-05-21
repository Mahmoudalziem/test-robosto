<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Contracts\InventoryControl as InventoryControlContract;
use Webkul\Area\Models\AreaProxy;
use Webkul\Inventory\Models\WarehouseProxy;

class InventoryControl extends Model implements InventoryControlContract
{
   protected $fillable = ['area_id','warehouse_id','start_date','end_date','is_active','is_completed'];
   
    public function warehouse() {
        return $this->belongsTo(WarehouseProxy::modelClass(), 'warehouse_id');
    }

    public function area() {
        return $this->belongsTo(AreaProxy::modelClass(), 'area_id');
    }
   
}