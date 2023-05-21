<?php

namespace Webkul\Sales\Models;

use Webkul\Sales\Models\OrderProxy;
use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Models\PaymobCardProxy;
use Webkul\Sales\Contracts\OrderPayment as OrderPaymentContract;


class OrderPayment extends Model implements OrderPaymentContract
{
    public const CASH_ON_DELIVERY   = 'COD';
    public const CREDIT_CARD        = 'CC';
    public const BNPL        = 'BNPL';

    protected $table = 'order_payment';

    protected $fillable = [
        'id',
        'method',
        'payment_method_id',
        'paymob_card_id',
        'order_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the order record associated with the payment.
     */
    public function order()
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }
    
    /**
     * Get the method record associated with the payment.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethodProxy::modelClass(), 'payment_method_id');
    }
    
    /**
     * Get the method record associated with the payment.
     */
    public function paymobCard()
    {
        return $this->belongsTo(PaymobCardProxy::modelClass(), 'paymob_card_id');
    }
}