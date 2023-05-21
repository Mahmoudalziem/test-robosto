<?php

namespace Webkul\Shipping\Http\Resources;

use Webkul\Inventory\Models\Warehouse;

class PickUpLocationResource
{
    public static function DTO($data)
    {



        $warehouse = Warehouse::where('status', 1)->where('area_id', $data["area_id"])->first();
        return [
            'name'          => $data["name"],
            'phone'         => $data["phone"],
            'address'         => $data["address"],
            'area_id'         => $data["area_id"],
            'warehouse_id'  => $warehouse->id,
            'latitude' => $data["location"]["lat"],
            'longitude' => $data["location"]["lng"],
        ];
    }
}
