<?php

namespace Webkul\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Shipping\Contracts\PickupLocation as PickupLocationContract;

class PickupLocation extends Model  implements PickupLocationContract
{
    protected $table = 'shippers_pickup_locations';
    protected $fillable = ['name','phone','address','area_id','warehouse_id','shipper_id','latitude','longitude'];
}
