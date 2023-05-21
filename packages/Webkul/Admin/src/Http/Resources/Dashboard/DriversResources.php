<?php

namespace Webkul\Admin\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\CustomResourceCollection;

class DriversResources extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($driver) {

            $driverData = Cache::get('driver_' . $driver->id);
            $lat = null;
            $long = null;

            if ($driverData != null) {
                $lat = (float) $driverData['lat'];
                $long = (float) $driverData['long'];
            }

            return [
                'id'            => $driver->id,
                'lat'            => $lat,
                'lng'            => $long,
            ];
        });
    }

}
