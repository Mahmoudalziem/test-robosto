<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Sales\Contracts\PaymentMethodTranslation as PaymentMethodTranslationContract;

class PaymentMethodTranslation extends Model implements PaymentMethodTranslationContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_method_translations';
    public $timestamps = false;

    protected $fillable = [
        'title', 'description', 'locale'
    ];
}