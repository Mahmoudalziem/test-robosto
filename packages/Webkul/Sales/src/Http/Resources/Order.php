<?php

namespace Webkul\Sales\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Webkul\Sales\Http\Resources\DriverResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Sales\Http\Resources\CollectorResource;

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

        return [
                'id' => $this->id,
                'increment_id' => $this->increment_id,
                'status' => $this->status,
                'status_name' => $this->status_name,
                'area' => $this->area->name,
                'warehouse' => $this->warehouse ? $this->warehouse->name : null,
                'collector' =>  $this->collector_id ? new CollectorResource($this->collector) : null,
                'driver' =>  $this->driver_id ? new DriverResource($this->driver) : null,
                'order_date' => $this->created_at,
                'expected_order_date' => $this->created_at,
                'delivered_at' => $this->created_at,
                'scheduled_at' => $this->scheduled_at,
                'payment_method' => $this->payment ? $this->payment->method : null,
                'payment_method_title' => $this->payment ? $this->payment->paymentMethod->title : null,
                'customer_name' =>  $this->customer->name,
                'customer_mobile' =>  $this->customer->phone,
                'customer_address_name' => $this->address->name,
                'customer_address' => $this->address->address,
                'customer_address_building_no' => $this->address->building_no,
                'customer_latitude' => $this->address->latitude,
                'customer_longitude' => $this->address->longitude,
                'order_sub_total' => $this->sub_total,
                'order_delivery_chargs' => $this->delivery_chargs,
                'order_tax_amount' => $this->tax_amount,
                'order_total' => $this->final_total,
                'items' => OrderItemResource::collection($this->items->sortBy('shelve_position')),
        ];

    }

}