<?php

namespace Webkul\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Driver\Contracts\DriverLogEmergency as DriverLogEmergencyContract;

class DriverLogEmergency extends Model implements DriverLogEmergencyContract
{
    protected $fillable = ['reason','driver_id','order_id'];
}