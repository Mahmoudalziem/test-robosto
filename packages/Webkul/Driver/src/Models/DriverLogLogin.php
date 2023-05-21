<?php

namespace Webkul\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Driver\Contracts\DriverLogLogin as DriverLogLoginContract;

class DriverLogLogin extends Model implements DriverLogLoginContract
{
    protected $fillable = ['driver_id','action'];


}