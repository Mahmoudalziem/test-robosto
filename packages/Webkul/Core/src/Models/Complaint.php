<?php

namespace Webkul\Core\Models;

use Webkul\Sales\Models\OrderProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Core\Contracts\Complaint as ComplaintContract;

class Complaint extends Model implements ComplaintContract
{
    public const COMPLAINT_TYPE         = 'complaint';
    public const SUGGEST_TYPE           = 'suggest';

    protected $fillable = ['text', 'type', 'customer_id', 'order_id'];

    /**
     * Get the customer record associated with the order.
     */
    public function customer()
    {
        return $this->belongsTo(CustomerProxy::modelClass());
    }

    /**
     * Get the order record associated with the order.
     */
    public function order()
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }
}