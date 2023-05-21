<?php

namespace Webkul\Sales\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailsSingle extends JsonResource
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

        return [
             //   'id' => $this->id,
                'id' => $this->id,
                'increment_id' => $this->increment_id,
                'order_no' =>  $this->increment_id,
                'status' => $this->status,
                'status_name' => $this->status_name,
                'order_date' => $this->created_at,
                'scheduled_at' => $this->scheduled_at,
                'ratings'   =>  $this->comment?$this->comment->rating:null,
                'delivered_at' => $this->delivered_at,
                'expected_on' => $this->expected_on,
                'payment_method' => $this->payment ? $this->payment->method : null,
                'payment_method_title' => $this->payment ? $this->payment->paymentMethod->title : null,
                'customer_name' =>  $this->customer->name,
                'customer_mobile' =>  $this->address->phone,
                'customer_address_name' => $this->address->name,
                'customer_address' => $this->address->address,
                'address'=>[ 'phone' =>  $this->address->phone,
                             'name' => $this->address->name,
                             'address' => $this->address->address,
                             'icon_id' => $this->customerAddress ? ($this->customerAddress->icon ? $this->customerAddress->icon->id : null) : null
                ],
                'customer_address_building_no' => $this->address->building_no,
                'customer_latitude' => $this->address->latitude,
                'customer_longitude' => $this->address->longitude,
                'collector' =>  $this->collector_id ? new CollectorResource($this->collector) : null,
                'driver' =>  $this->driver_id ? new DriverResource($this->driver) : null,
                'order_sub_total' => $this->sub_total,
                'order_delivery_chargs' => $this->delivery_chargs,
                'order_tax_amount' => $this->tax_amount,
                'order_total' => $this->final_total,
                'price' => $this->final_total,
                'note' => $this->note,
                'items' => OrderItemResource::collection($this->items->where('qty_shipped', '>', 0)),
                'orderItems' => OrderItemResource::collection($this->items->where('qty_shipped', '>', 0)),
        ];

    }

}