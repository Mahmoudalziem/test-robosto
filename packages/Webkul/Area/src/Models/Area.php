<?php

namespace Webkul\Area\Models;

use Webkul\Area\Models\AreaProxy;
use Webkul\User\Models\AdminProxy;
//use Webkul\Area\Contracts\Area as AreaContract;
use Webkul\Bundle\Models\BundleProxy;
use Webkul\Driver\Models\DriverProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Area\Events\AreaPendingMoney;
use Webkul\Area\Events\MoneyAddedToArea;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Area\Models\AreaOpenHourProxy;
use Webkul\Discount\Models\DiscountProxy;
use Webkul\Area\Models\AreaClosedHourProxy;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Inventory\Models\WarehouseProxy;
use Webkul\Promotion\Models\PromotionProxy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Core\Eloquent\BaseEloquentBuilder;
use Webkul\Area\Contracts\Area as AreaContract;
use Webkul\Area\Events\MoneySubtractedFromArea;
use Webkul\Inventory\Models\InventoryAreaProxy;
use Webkul\Area\Events\AreaPendingMoneyReceived;
use Webkul\Area\Events\AreaPendingMoneyCancelled;
use Webkul\Inventory\Models\InventoryWarehouseProxy;
use Webkul\Driver\Models\DriverTransactionRequestProxy;

class Area extends TranslatableModel implements AreaContract {

    use SoftDeletes;

    public $translatedAttributes = ['name'];
    protected $fillable = [
        'status', 'main_area_id', 'min_distance_between_orders', 'drivers_on_the_way','show_in_app'
    ];
    protected $hidden = ['translations'];

    /**
     * Scope a query to only include active products.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query) {
        return $query->where('status', 1);
    }


    public function scopeAllowedInApp(Builder $query) {
        return $query->where('show_in_app', 1);
    }

    public function mainArea()
    {
        return $this->belongsTo(AreaProxy::modelClass(), 'main_area_id');
    }
    
    public function shadowAreas()
    {
        return $this->hasMany(AreaProxy::modelClass(), 'main_area_id');
    }

    /**
     * Get warehouses that belongs to the area
     */
    public function warehouses() {
        return $this->hasMany(WarehouseProxy::modelClass());
    }

    public function drivers()
    {
        return $this->hasMany(DriverProxy::modelClass());
    }

    public function transactions() {
        return $this->hasMany(DriverTransactionRequestProxy::modelClass());
    }

    public function products() {
        return $this->belongsToMany(ProductProxy::modelClass(), 'inventory_areas', 'area_id', 'product_id')->withPivot('area_id', 'init_total_qty', 'total_qty');
    }

    public function promotions() {
        return $this->belongsToMany(PromotionProxy::modelClass())->withTimestamps();
    }

    public function discounts() {
        return $this->belongsToMany(DiscountProxy::modelClass())->withTimestamps();
    }
    
    public function bundles() {
        return $this->belongsToMany(BundleProxy::modelClass())->withTimestamps();
    }    

    public function admins() {
        return $this->belongsToMany(AdminProxy::modelClass(), 'admin_areas')->withTimestamps();
    }

    public function discount() {
        return $this->belongsToMany(DiscountProxy::modelClass(), 'discount_areas')->withTimestamps();
    }

    public function openHours() {
        return $this->hasMany(AreaOpenHourProxy::modelClass());
    }

    
    public function closedHours() {
        return $this->hasMany(AreaClosedHourProxy::modelClass());
    }

    public function scopeByArea(Builder $query) {
        $user = auth()->user();
        if ($user) {
            if (!$user->hasRole(['super-admin', 'operation-manager'])) {
                return $this->whereIn('id', $user->areas->pluck('id'));
            }
            return $this;
        }
    }

    public function newEloquentBuilder($query)
    {
        return new BaseEloquentBuilder($query);
    }

    /**
     * @param float $amount
     * @param int|null $driverId
     * @param int|null $areaManagerId
     * 
     * @return mixed
     */
    public function addMoney(float $amount, int $driverId = null, int $areaManagerId = null) {
        event(new MoneyAddedToArea($this->id, $amount, $driverId, $areaManagerId));
    }

    /**
     * @param float $amount
     * @param int|null $driverId
     * @param int|null $areaManagerId
     * 
     * @return mixed
     */
    public function pendingMoney(float $amount, int $areaManagerId = null) {
        event(new AreaPendingMoney($this->id, $amount, $areaManagerId));
    }

    /**
     * @param float $amount
     * @param int|null $driverId
     * @param int|null $accountantId
     * 
     * @return mixed
     */
    public function pendingMoneyReceived(float $amount, int $accountantId = null) {
        event(new AreaPendingMoneyReceived($this->id, $amount, $accountantId));
    }

    /**
     * @param float $amount
     * @param int|null $driverId
     * 
     * @return mixed
     */
    public function pendingMoneyCancelled(float $amount) {
        event(new AreaPendingMoneyCancelled($this->id, $amount));
    }

    /**
     * @param float $amount
     * @param int|null $areaManagerId
     * @param int|null $accounatntId
     * 
     * @return mixed
     */
    public function subtractMoney(float $amount, int $areaManagerId = null, int $accounatntId = null) {
        event(new MoneySubtractedFromArea($this->id, $amount, $areaManagerId, $accounatntId));
    }

}
