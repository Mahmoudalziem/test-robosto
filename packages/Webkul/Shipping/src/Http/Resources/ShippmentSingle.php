<?php

namespace Webkul\Shipping\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippmentSingle extends JsonResource {

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
            'number'=>$this->shipping_number,
            'merchant'=>$this->merchant,
            'shipper'=>$this->shipper,
            'current_area'=>$this->area,
            'current_warehouse'=>$this->warehouse,
            'shipping_address'=>$this->shippingAddress,
            'dispatching_at'=>isset($this->first_trial_data)?$this->first_trial_data:null,
            'pickup_location'=>$this->pickupLocation,
            'items_count'=>$this->items_count,
            'price'=>$this->final_total,
            'status' => $this->status,
            'current_status' => $this->current_status,
            'note' => $this->note,
            'description'=>$this->description,
            'created_at'    => isset($this->created_at)?$this->created_at:null,
            'timeline'  =>$this->handleTimeline(),
            'is_settled'=>$this->is_settled,
            'is_rts'=>$this->is_rts
        ];
    }

}
