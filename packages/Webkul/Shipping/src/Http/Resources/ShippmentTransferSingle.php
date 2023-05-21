<?php

namespace Webkul\Shipping\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippmentTransferSingle extends JsonResource {

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
        return [
            'id'            => $this->id,
            'shippment_number'=>$this->shippment->shipping_number,
            'shippment_items_count'=>$this->shippment->items_count,
            'created_by'=>$this->admin_id?$this->admin->name:'Shipping System',                 
            'source'         => isset($this->from_warehouse_id)?$this->fromWarehouse->name:'-',
            'destinasation'     => isset($this->to_warehouse_id)?$this->toWarehouse->name:'-',
            'status' => $this->status,
            'created_at'    => isset($this->created_at)?$this->created_at:null,
            'timeline'  =>$this->timeline
        ];
    }

}
