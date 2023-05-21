<?php

namespace Webkul\Admin\Http\Resources\Sales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Customer\Models\CustomerAddress;
use Webkul\Promotion\Models\Promotion;

class SelectedAddress extends JsonResource
{
    protected $append;
    public function __construct($resource,$append=null)
    {
        $this->append=$append;
        parent::__construct($resource);

    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {
        $address = CustomerAddress::find($this->address_id);
        
        return [
            'id' =>     $address->id,
            "area_id"   => $address->area_id,
            "icon_id"   => $address->icon_id,
            "name"  => $address->name,
            "address"   => $address->address,
            "floor_no"  => $address->floor_no,
            "apartment_no"  => $address->apartment_no,
            "building_no"   => $address->building_no,
            "landmark"  => $address->landmark,
            "latitude"  => $address->latitude,
            "longitude" => $address->longitude,
            "phone" => $address->phone,
            "is_default"    => $address->is_default,
            "covered"   => $address->covered,
            "created_at"    => $address->created_at,
            "updated_at"    => $address->updated_at,
            "deleted_at"    => $address->deleted_at,
            "area_name" => $address->area_name,
            "icon"  =>   $address->icon,
        ];

    }

}
