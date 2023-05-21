<?php

namespace Webkul\Admin\Http\Resources\Inventory;

use App\Http\Resources\CustomResourceCollection;

class InventoryTransactionAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {


        return  $this->collection->map(function ($inventoryTransaction) {

            if($inventoryTransaction->status==0)
            {
                $status='Canceled';
            }
            elseif($inventoryTransaction->status==1)
            {
                $status='Pending';
            }
            elseif($inventoryTransaction->status==2)
            {
                $status='On the way';
            }
            elseif($inventoryTransaction->status==3){
                $status='Transffered';
            }
            return [
                'id'            => $inventoryTransaction->id,
                'created_by'=>$inventoryTransaction->createdBy->name ?? null,                 
                'source'         => isset($inventoryTransaction->from_warehouse_id)?$inventoryTransaction->fromWarehouse->name:'-',
                'destinasation'     => isset($inventoryTransaction->to_warehouse_id)?$inventoryTransaction->toWarehouse->name:'-',
                'transaction_type'  => $inventoryTransaction->transaction_type=='inside'? __('admin::app.inside'):__('admin::app.outside'),

                'status' => $status,

              //  'source' => (string)   isset($inventoryTransaction->channel->name)?$inventoryTransaction->channel->name:'-' ,

                'created_at'    => isset($inventoryTransaction->created_at)?$inventoryTransaction->created_at:null,
            ];
        });
    }

}