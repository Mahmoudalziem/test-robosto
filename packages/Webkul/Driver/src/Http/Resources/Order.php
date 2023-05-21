<?php

namespace Webkul\Driver\Http\Resources;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Order extends JsonResource
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
        if ($this->shippment_id) {
            if(!$this->customer){
                $customer = new Collection();
                if($this->warehouse){
                    $customer->name =  $this->warehouse->contact_name;
                    $customer->phone = $this->warehouse->contact_number;
                }else{
                    $customer->name = "Shippment";
                    $customer->phone = "";
                }
                $this->customer = $customer;
            }else{
                $this->customer->name ="Shippment To ".$this->customer->name;
            }
            $number = $this->shippment->shipping_number;
            $company = $this->shippment->shipper->name;
            $this->note = " شحنه رقم $number لشركة $company ";
        }
        return [
                'id' => $this->id,
                'increment_id' => $this->increment_id,
                'order_date' => $this->created_at,
                'expected_order_date' => $this->created_at,
                'expected_delivered_date' => $this->expected_delivered_date,
                'payment_method' => null,
                'customer_name' =>  $this->customer->name,
                'customer_mobile' =>  config('robosto.ROBOSTO_PHONE'),
                'customer_address' => $this->address->address,
                'customer_latitude' => $this->address->latitude,
                'customer_longitude' => $this->address->longitude,
                'note' => $this->note,
                'order_total' => $this->final_total,
                'items' => OrderItemResource::collection($this->items->where('qty_shipped', '>', 0)),
        ];

    }

}