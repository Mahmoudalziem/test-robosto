<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\BonusVariable as BonusVariableContract;

class BonusVariable extends Model implements BonusVariableContract
{
    protected $table = 'bonus_variables';

    protected $fillable = ['orders', 'orders_bonus', 'working_hours', 'working_hours_bonus'];
}