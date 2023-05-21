<?php

namespace Webkul\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Webkul\Driver\Contracts\DriverStatusLog as DriverStatusLogContract;

class DriverStatusLog extends Model implements DriverStatusLogContract
{
    protected $fillable = ['driver_id','period','availability','status_log_date'];
    protected $casts=['status_log_date'=> 'date'];
}