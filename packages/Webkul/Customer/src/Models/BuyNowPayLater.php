<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Contracts\BuyNowPayLater as BuyNowPayLaterContract;
use Webkul\Sales\Models\OrderProxy;

class BuyNowPayLater extends Model implements BuyNowPayLaterContract
{
    protected $table = 'buy_now_pay_later_transactions';

    protected $fillable = [
        'customer_id',
        'order_id',
        'amount',
        'status',
        'release_date'
    ];

    /**
     * The Product that belong to the wishlist.
     */
    public function order()
    {
        return $this->hasOne(OrderProxy::modelClass(), 'id', 'order_id');
    }

    public function customer()
    {
        return $this->hasOne(CustomerProxy::modelClass(), 'id', 'customer_id');
    }
}
