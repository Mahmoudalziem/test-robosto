<?php

namespace Webkul\Admin\Http\Resources\Inventory;

use App\Http\Resources\CustomResourceCollection;

class InventoryAdjustmentAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {


        return  $this->collection->map(function ($inventoryAdjustment) {



            if($inventoryAdjustment->status==0)
            {
                $status='Canceled';
            }
            elseif($inventoryAdjustment->status==1)
            {
                $status='Pending';
            }
            elseif($inventoryAdjustment->status==2)
            {
                $status='Approved';
            }

            return [
                'id'            => $inventoryAdjustment->id,
                'warehouse'         => $inventoryAdjustment->warehouse->name,
                'status' => $inventoryAdjustment->status,
                'statusName' => $status,
                'area'         => $inventoryAdjustment->warehouse->area->name,
                'created_at'    =>$inventoryAdjustment->created_at,
            ];
        });
    }

}