<?php

namespace Webkul\Purchase\Models;

use Illuminate\Support\Str;
use Webkul\Area\Models\AreaProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Core\Models\ActivityLogProxy;
use Webkul\Supplier\Models\SupplierProxy;
use Webkul\Inventory\Models\WarehouseProxy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Inventory\Models\InventoryProductProxy;
use Webkul\Purchase\Contracts\PurchaseOrder as PurchaseOrderContract;
use Webkul\User\Models\AdminProxy;

class PurchaseOrder extends Model implements PurchaseOrderContract {

    use SoftDeletes;

    protected $fillable = [
        'invoice_no',
        'purchase_order_no',
        'is_draft',
        'sub_total_cost',
        'discount_type',
        'discount',
        'total_cost',
        'area_id',
        'warehouse_id',
        'supplier_id',
        'admin_id'
    ];

    /**
     * Get all Logs
     */
    public function logs() {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    public function products() {
        return $this->hasMany(PurchaseOrderProductProxy::modelClass(), 'purchase_order_id');
    }

    public function area() {
        return $this->belongsTo(AreaProxy::modelClass());
    }

    public function warehouse() {
        return $this->belongsTo(WarehouseProxy::modelClass());
    }

    public function supplier() {
        return $this->belongsTo(SupplierProxy::modelClass());
    }

    public function newEloquentBuilder($query) {
        return new \Webkul\Core\Eloquent\BaseEloquentBuilder($query);
    }

    public function createdBy() {
        return $this->belongsTo(AdminProxy::modelClass(), 'admin_id');
    }

}
