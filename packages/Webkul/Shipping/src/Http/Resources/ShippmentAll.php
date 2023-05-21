<?php

namespace Webkul\Shipping\Http\Resources;

use App\Http\Resources\CustomResourceCollection;

class ShippmentAll extends CustomResourceCollection
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
                'merchant'=>$shippment->merchant,
                'shipper'=>$shippment->shipper,
                'current_area'=>$shippment->area,
                'current_warehouse'=>$shippment->warehouse,
                'shipping_address'=>$shippment->shippingAddress,
                'dispatching_at'=>isset($shippment->first_trial_data)?$shippment->first_trial_data:null,
                'pickup_location'=>$shippment->pickupLocation,
                'items_count'=>$shippment->items_count,
                'price'=>$shippment->final_total,
                'status' => $shippment->status,
                'current_status' => $shippment->current_status,
                'note' => $shippment->note,
                'description'=>$shippment->description,
                'is_rts'=>$shippment->is_rts,
                'created_at'    => isset($shippment->created_at)?$shippment->created_at:null
            ];
        });
    }

}