<?php

namespace Webkul\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Driver\Contracts\MonthlyBonus as MonthlyBonusContract;

class MonthlyBonus extends Model implements MonthlyBonusContract
{
    protected $table = 'monthly_bonus';

    protected $fillable = [
        'no_of_orders', 'no_of_working_hours', 'cutomer_ratings', 'supervisor_ratings', 'working_path_ratings', 'orders_bonus', 
        'working_hours_bonus', 'back_bonus', 'no_of_orders_back_bonus', 'bonus', 'equation', 'driver_id'
    ];

    public function driver()
    {
        return $this->belongsTo(DriverProxy::modelClass());
    }
}