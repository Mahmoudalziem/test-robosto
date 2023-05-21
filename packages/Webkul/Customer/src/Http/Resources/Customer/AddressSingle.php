<?php

namespace Webkul\Customer\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Carbon;


class AddressSingle extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return array
     */
    public function toArray($request)
    {
        return [

                "id"    => $this->id,
                "customer_id"   => $this->customer_id,
                "area_id"   => $this->area_id,
                "icon_id"   => $this->icon_id,
                "name"  => $this->name,
                "address"   => $this->address,
                "floor_no"  => $this->floor_no,
                "apartment_no"  => $this->apartment_no,
                "building_no"   => $this->building_no,
                "landmark"  => $this->landmark,
                "latitude"  => $this->latitude,
                "longitude" => $this->longitude,
                "phone" => $this->phone,
                "is_default"    => $this->is_default,
                "covered"   => $this->covered,
                "created_at"    => $this->created_at,
                "updated_at"    => $this->updated_at,
                "deleted_at"    => $this->deleted_at,
                "area_name" => $this->area_name,
                "icon"  =>   $this->icon,
        ];
    }
}