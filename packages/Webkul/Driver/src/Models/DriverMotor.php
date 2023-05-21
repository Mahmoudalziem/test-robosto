<?php

namespace Webkul\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Driver\Contracts\DriverMotor as DriverMotorContract;

class DriverMotor extends Model implements DriverMotorContract
{
    protected $fillable = [];
    protected $table="driver_motor";
}