<?php

namespace Webkul\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Driver\Contracts\DailyBonus as DailyBonusContract;

class DailyBonus extends Model implements DailyBonusContract
{
    protected $table = 'daily_bonus';

    protected $fillable = [
        'no_of_orders', 'no_of_working_hours', 'cutomer_ratings', 'working_path_ratings', 'back_bonus', 'no_of_orders_back_bonus', 'bonus', 'driver_id'
    ];

    public function driver()
    {
        return $this->belongsTo(DriverProxy::modelClass());
    }
}