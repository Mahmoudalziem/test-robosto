<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\RetentionCustomer as RetentionCustomerContract;
use Webkul\Customer\Models\CustomerProxy;

class RetentionCustomer extends Model implements RetentionCustomerContract
{
    protected $table = 'retention_customers';

    protected $fillable = ['retention_id', 'customer_id', 'used'];

    public function retention()
    {
        return $this->belongsTo(RetentionMessageProxy::modelClass(), 'retention_id');
    }
    
    public function customer()
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }
}