<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Astrotomic\Translatable\Translatable;
use Webkul\Sales\Contracts\PaymentMethod as PaymentMethodContract;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class PaymentMethod extends Model implements PaymentMethodContract
{
    use Translatable;

    public $translatedAttributes = [
        'title', 'description'
    ];

    protected $fillable = ['slug', 'status'];

    /**
     * Scope a query to only include active products.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 1);
    }
}