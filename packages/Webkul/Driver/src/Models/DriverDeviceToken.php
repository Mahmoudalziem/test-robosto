<?php

namespace Webkul\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Driver\Contracts\DriverDeviceToken as DriverDeviceTokenContract;

class DriverDeviceToken extends Model implements DriverDeviceTokenContract
{
    protected $fillable = ['token'];
}