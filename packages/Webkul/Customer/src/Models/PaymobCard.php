<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Customer\Contracts\PaymobCard as PaymobCardContract;

class PaymobCard extends Model implements PaymobCardContract
{

    public const VISA_TYPE = 'visa';
    public const MASTERCARD_TYPE = 'mastercard';
    public const MEEZA_TYPE = 'meeza';

    protected $table = 'paymob_cards';
    protected $fillable = ['last_four', 'token', 'order_id', 'brand', 'email', 'customer_id', 'is_default','status'];

    /**
     * Get the customer that owns the card.
     */
    public function customer()
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    /**
     * Scope a query to only include active categories.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', '1');
    }
}
