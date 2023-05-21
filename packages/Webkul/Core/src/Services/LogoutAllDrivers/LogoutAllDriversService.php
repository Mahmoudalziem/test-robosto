<?php

namespace Webkul\Core\Services\LogoutAllDrivers;

use Carbon\Carbon;
use Webkul\Area\Models\Area;
use Webkul\Area\Models\AreaClosedHour;
use Webkul\Driver\Models\Driver;

class LogoutAllDriversService
{
    
    /**
     * @return void
     */
    public function startLogoutDrivers()
    {
        foreach (Area::all() as $area) {

            Carbon::setLocale("en");

            $areaClosedHours = AreaClosedHour::where("area_id", $area->id)
                ->where("from_day", Carbon::now()->dayName)
                ->where('from_hour', '<', Carbon::now()->format('H:i:s'))
                ->where('to_hour', '>=', Carbon::now()->format('H:i:s'))
                ->first();

            if ($areaClosedHours) {
                
                $area->drivers()->where('is_online', 1)
                    ->where('availability', '!=', Driver::AVAILABILITY_DELIVERY)
                    ->update([
                        'is_online' => 0,
                        'availability'  =>  Driver::AVAILABILITY_OFFLINE,
                        'can_receive_orders'    => Driver::CANNOT_RECEIVE_ORDERS
                    ]);
            }
        }
    }
}
