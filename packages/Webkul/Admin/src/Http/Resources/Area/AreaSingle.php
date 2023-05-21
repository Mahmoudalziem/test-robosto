<?php

namespace Webkul\Admin\Http\Resources\Area;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Admin\Http\Resources\Warehouse\WarehouseAll;

class AreaSingle extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'default' => $this->default,
            'main_area_id' => $this->main_area_id,
            'drivers_on_the_way' => $this->drivers_on_the_way,
            'min_distance_between_orders' => $this->min_distance_between_orders,
            'wallet' => $this->wallet,
            'total_wallet' => $this->total_wallet,
            'pending_wallet' => $this->pending_wallet,
            'ar'         => ['name' => $this->translate('ar')->name],
            'en'         => ['name' => $this->translate('en')->name],
            "area_borders"  =>  $this->getAreaBorders($this->id),
            'warehouses_count'  =>  count($this->warehouses),
            'drivers_count'  =>  count($this->drivers),
            'warehouses'    =>  new WarehouseAll($this->warehouses),
            'created_at' => $this->created_at
        ];
    }

    /**
     * @param int $id
     * 
     * @return array
     */
    private function getAreaBorders(int $id)
    {
        $allAreaBorders = config('areas.locations');

        if (isset($allAreaBorders[$id])) {
            return $allAreaBorders[$id];
        }
        return null;
    }
}
