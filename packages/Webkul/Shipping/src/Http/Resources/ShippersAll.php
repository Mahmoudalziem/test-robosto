<?php

namespace Webkul\Shipping\Http\Resources;

use App\Http\Resources\CustomResourceCollection;

class ShippersAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($shipper) {
            return [
                'id'            => $shipper->id,
                'name'=>$shipper->name,
                'email'=>$shipper->email,
                'created_at'    => isset($shipper->created_at)?$shipper->created_at:null
            ];
        });
    }

}