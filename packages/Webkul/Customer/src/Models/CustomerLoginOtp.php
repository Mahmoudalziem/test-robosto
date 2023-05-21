<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Customer\Contracts\CustomerLoginOtp as CustomerLoginOtpContract;

class CustomerLoginOtp extends Model implements CustomerLoginOtpContract
{
    
    protected $fillable = ['customer_id', 'otp', 'expired_at'];

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = null;


    /**
     * Customer who has this otp
     */
    public function customer()
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }
}