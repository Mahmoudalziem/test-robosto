<?php

namespace Webkul\Shipping\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippmentBulkTransferSingle extends JsonResource {

    protected $append;

    public function __construct($resource, $append = null) {
        $this->append = $append;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {
        $shippments = [];
        foreach ($this->bulkTransferItems as $item){
            array_push($shippments,[
                'id'=>$item->shippment->id,
                'shipping_number'=>$item->shippment->shipping_number,
                'merchant'=>$item->shippment->merchant,
                'final_total'=>$item->shippment->final_total
            ]);
        }
        return [
            'id'            => $this->id,
            'created_by'=>$this->admin_id?$this->admin->name:'Shipping System',                 
            'source'         => isset($this->from_warehouse_id)?$this->fromWarehouse->name:'-',
            'destinasation'     => isset($this->to_warehouse_id)?$this->toWarehouse->name:'-',
            'status' => $this->status,
            'created_at'    => isset($this->created_at)?$this->created_at:null,
            'shipments'  =>$shippments
        ];
    }

}
