<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\User\Contracts\AdminDeviceToken as AdminDeviceTokenContract;

class AdminDeviceToken extends Model implements AdminDeviceTokenContract
{

    protected $fillable = [
        'admin_id',
        'token',
    ];
}