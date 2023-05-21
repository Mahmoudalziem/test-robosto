<?php

namespace Webkul\Admin\Http\Resources\Area;

use App\Http\Resources\CustomResourceCollection;

class AreaAll extends CustomResourceCollection
{

    public function toArray($request)
    {
        return $this->collection->map(function ($area) {

            return [
                'id' => $area->id,
                'status' => $area->status,
                'default' => $area->default,
                'main_area_id' => $area->main_area_id,
                'drivers_on_the_way' => $area->drivers_on_the_way,
                'min_distance_between_orders' => $area->min_distance_between_orders,
                'wallet' => $area->wallet,
                'total_wallet' => $area->total_wallet,
                'pending_wallet' => $area->pending_wallet,
                'ar'         => ['name' => $area->translate('ar')->name],
                'en'         => ['name' => $area->translate('en')->name],
                'warehouses_count'  =>  count($area->warehouses),
                'drivers_count'  =>  count($area->drivers),
                'created_at' => $area->created_at
            ];
        });
    }
}
