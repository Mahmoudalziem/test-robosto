<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Customer\Contracts\CustomerSetting as CustomerSettingContract;

class CustomerSetting extends Model implements CustomerSettingContract
{
    protected $fillable = ['customer_id','key','value','group'];

    public function customer()
    {
        $this->belongsTo(CustomerProxy::modelClass());
    }
}