<?php

namespace Webkul\Shipping\Http\Resources;

use App\Http\Resources\CustomResourceCollection;

class SimpleShippmentAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {


        return  $this->collection->map(function ($shippment) {

            return [
                'id'            => $shippment->id,
                'number'=>$shippment->shipping_number,
            ];
        });
    }

}