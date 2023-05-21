<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Contracts\CustomerFavoriteProducts as CustomerFavoriteProductsContract;
use Webkul\Product\Models\ProductProxy;

class CustomerFavoriteProducts extends Model implements CustomerFavoriteProductsContract
{
    protected $table = 'favorite_customers_products';

    protected $fillable = [
        'customer_id',
        'product_id',
        'favorite'
    ];

    public function product()
    {
        return $this->belongsTo(ProductProxy::class);
    }


    public function customer()
    {
        return $this->belongsTo(CustomerProxy::class);
    }
}
