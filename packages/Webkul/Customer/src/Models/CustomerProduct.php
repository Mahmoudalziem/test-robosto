<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Customer\Contracts\CustomerSetting as CustomerSettingContract;

class CustomerProduct extends Model implements CustomerSettingContract
{

    protected $table="customer_products";

    public function customer()
    {
        $this->belongsTo(CustomerProxy::modelClass());
    }

    public function product()
    {
        $this->belongsTo(ProductProxy::modelClass());
    }
}