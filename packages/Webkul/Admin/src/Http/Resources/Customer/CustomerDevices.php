<?php

namespace Webkul\Admin\Http\Resources\Customer;

use App\Http\Resources\CustomResourceCollection;

class CustomerDevices extends CustomResourceCollection
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map( function ($device) {
                return [
                    'id'            => $device->id,
                    'device_type'   => $device->device_type,
                    'device_id'     => $device->device_id,
                ];
            }
        );
    }
}
