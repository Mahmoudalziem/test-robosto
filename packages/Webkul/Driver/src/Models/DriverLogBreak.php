<?php

namespace Webkul\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Driver\Contracts\DriverLogBreak as DriverLogBreakContract;

class DriverLogBreak extends Model implements DriverLogBreakContract
{
    protected $fillable = ['duration','driver_id'];
}