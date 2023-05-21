<?php

namespace Webkul\Shipping\Http\Resources;

use App\Http\Resources\CustomResourceCollection;

class ShippmentBulkTransferAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {


        return  $this->collection->map(function ($shippingTransfer) {

            return [
                'id'            => $shippingTransfer->id,
                'created_by'=>$shippingTransfer->admin_id?$shippingTransfer->admin->name:'Shipping System',                 
                'source'         => isset($shippingTransfer->from_warehouse_id)?$shippingTransfer->fromWarehouse->name:'-',
                'destinasation'     => isset($shippingTransfer->to_warehouse_id)?$shippingTransfer->toWarehouse->name:'-',
                'status' => $shippingTransfer->status,
                'created_at'    => isset($shippingTransfer->created_at)?$shippingTransfer->created_at:null,
            ];
        });
    }

}