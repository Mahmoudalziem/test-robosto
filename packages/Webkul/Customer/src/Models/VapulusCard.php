<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Contracts\VapulusCard as VapulusCardContract;

class VapulusCard extends Model implements VapulusCardContract
{
    public const VISA_TYPE = 'visa';
    public const MASTERCARD_TYPE = 'mastercard';

    protected $table = 'vapulus_cards';

    protected $fillable = ['last_digits', 'card_id', 'user_id', 'type', 'customer_id'];


    /**
     * Get the customer that owns the card.
     */
    public function customer()
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }
}