<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Shipping\Contracts\ShippingAddress as ShippingAddressContract;

class ShippingAddress extends Model implements ShippingAddressContract
{
    protected $table = 'shipping_addresses';
    protected $fillable = ['name','email','phone','address','area_id','shipper_id','landmark','apartment_no','building_no','floor_no'];
}
