<?php

namespace Webkul\Collector\Models;

use Illuminate\Database\Eloquent\Model;

use Webkul\Collector\Contracts\CollectorDeviceToken as CollectorDeviceTokenContract;

class CollectorDeviceToken extends Model implements CollectorDeviceTokenContract
{
    protected $fillable = ['token'];
}