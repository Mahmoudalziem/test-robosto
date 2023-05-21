<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Customer\Contracts\AddressIcon as AddressIconContract;

class AddressIcon extends Model implements AddressIconContract
{
    protected $table = 'address_icons';
    protected $fillable = ['image'];

    /**
     * Get warehouses that belongs to the area
     */
    public function customers()
    {
        return $this->hasMany(CustomerProxy::modelClass(), 'icon_id');
    }
}