<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Customer\Contracts\CustomerDeviceToken as CustomerDeviceTokenContract;

class CustomerDeviceToken extends Model implements CustomerDeviceTokenContract
{
    protected $fillable = ['token', 'device_id', 'device_type', 'customer_id'];

    public function customer()
    {
        $this->belongsTo(CustomerProxy::modelClass());
    }
}