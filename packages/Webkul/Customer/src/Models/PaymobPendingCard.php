<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Contracts\PaymobPendingCard as PaymobPendingCardContract;

class PaymobPendingCard extends Model implements PaymobPendingCardContract
{
    public const VISA_TYPE = 'visa';
    public const MASTERCARD_TYPE = 'mastercard';
    public const MEEZA_TYPE = 'meeza';

    protected $table = 'paymob_pending_cards';
    protected $fillable = ['last_four', 'token', 'order_id', 'brand', 'email', 'customer_id', 'is_default'];

    /**
     * Get the customer that owns the card.
     */
    public function customer() {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }
}