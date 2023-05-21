<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Contracts\CustomerPayment as CustomerPaymentContract;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Sales\Models\OrderProxy;
use Webkul\Customer\Models\PaymobCardProxy;

class CustomerPayment extends Model implements CustomerPaymentContract {

    //protected $fillable = ['customer_id', 'order_id', 'paymob_card_id', 'paymob_order_id', 'paymob_transaction_id', 'amount', 'payload_response', 'is_paid'];
    protected $fillable = ['customer_id', 'order_id', 'paymob_card_id', 'paymob_order_id', 'paymob_transaction_id', 'amount', 'payload_response', 'is_paid'];
    protected $table = 'customer_payments';
    protected $casts = [
        'payload_response' => 'array'
    ];

    public function customer() {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    public function order() {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }

    public function paymobCard() {
        return $this->belongsTo(PaymobCardProxy::modelClass(), 'paymob_card_id');
    }

}
