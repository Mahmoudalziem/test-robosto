<?php
namespace Webkul\Motor\Models;

use phpDocumentor\Reflection\Types\Boolean;
use Webkul\Motor\Contracts\Motor as MotorContract;
use Illuminate\Database\Eloquent\Model;
use Webkul\Driver\Models\Driver;

class Motor extends Model implements MotorContract
{
    protected $fillable=['chassis_no','license_plate_no','condition','status'];
    protected $casts=['status'=> 'boolean'];

    public function drivers()
    {
        return $this->belongsToMany(Driver::class)
            ->withPivot('motor_condition','image', 'status')
            ->withTimestamps();
    }

}