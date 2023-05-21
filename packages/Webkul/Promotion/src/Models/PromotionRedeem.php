<?php

namespace Webkul\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Product\Models\ProductProxy;
use Webkul\Promotion\Contracts\PromotionRedeem as PromotionRedeemContract;

class PromotionRedeem extends Model implements PromotionRedeemContract
{
    protected $table = 'promotion_redeems';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'promotion_id',
        'customer_id',
        'redeems_count'
    ];

    public function customer() {
        return $this->belongsTo(CustomerProxy::modelClass());
    }

    public function promotion() {
        return $this->belongsTo(PromotionProxy::modelClass());
    }
}