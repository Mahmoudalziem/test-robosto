<?php

namespace Webkul\Area\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Area\Contracts\AreaOpenHour as AreaOpenHourContract;

class AreaOpenHour extends Model implements AreaOpenHourContract
{
   protected $fillable = ['area_id','rank','from_day','from_hour','to_day','to_hour'];
}