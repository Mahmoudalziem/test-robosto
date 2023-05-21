<?php

namespace Webkul\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Promotion\Contracts\PromotionVoidDevice as PromotionVoidDeviceContract;
use Webkul\Promotion\Models\PromotionProxy;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Sales\Models\OrderProxy;
use Webkul\Sales\Models\Order;

class PromotionVoidDevice extends Model implements PromotionVoidDeviceContract
{
    protected $fillable = ['promotion_id','customer_id','order_id','deviceid'];
    protected $appends = ['orders']; 
    
    public $timestamps = true;
    
    public function promotion() {
        return $this->belongsTo(PromotionProxy::modelClass());
    }    
    
    public function customer() {
        return $this->belongsTo(CustomerProxy::modelClass());
    }

    public function order() {
        return $this->belongsTo(OrderProxy::modelClass());
    } 
    
    public function getOrdersAttribute()
    {
        if (! $this->deviceid) {
            return null;
        }
     
          $orderIDs=$this->where('deviceid',$this->deviceid)->pluck('order_id')->toArray();
// 
         return Order::whereIn('id',$orderIDs)->get(['id','customer_id']); 
         
    } 
    
    public function listOrders()
    {
        if (! $this->deviceid) {
            return null;
        }
     
        $orderIDs=$this->where('deviceid',$this->deviceid)->pluck('order_id')->toArray();
 
        return Order::whereIn('id',$orderIDs)->get(['id','customer_id']); 
         
    }    
}