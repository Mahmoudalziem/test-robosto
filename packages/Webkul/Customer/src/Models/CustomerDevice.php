<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Promotion\Models\PromotionVoidDeviceProxy;
use Webkul\Customer\Contracts\CustomerDevice as CustomerDeviceContract;

class CustomerDevice extends Model implements CustomerDeviceContract {

    protected $fillable = ['customer_id', 'deviceid'];

    
    public function customer() {
        return $this->belongsTo(CustomerProxy::modelClass());
    }

    public function order() {
        return $this->belongsTo(PromotionVoidDeviceProxy::modelClass(),'deviceid','deviceid');
    } 

    
    public function myAccounts() {
        return $this->where('deviceid',$this->deviceid)->get();
    }

}
