<?php

namespace Webkul\Area\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Area\Contracts\AreaClosedHour as AreaClosedHourContract;

class AreaClosedHour extends Model implements AreaClosedHourContract
{
    protected $fillable = ['area_id','rank','from_day','from_hour','to_day','to_hour'];
}