<?php

namespace Webkul\Collector\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderReturn extends JsonResource
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
        $orderCached= $this->append;

        return [

                'id' => $this->id,
                'increment_id' => $this->increment_id,
                'order_date' => $this->created_at,
                'expected_order_date' => $this->created_at,
                'expected_delivered_date' => $this->expected_delivered_date,
                'payment_method' => null,
                'customer_name' =>  $this->customer->name,
                'customer_mobile' =>  $this->customer->phone,
                'customer_address' => $this->address->address,
                'customer_latitude' => $this->address->latitude,
                'customer_longitude' => $this->address->longitude,
                'note' => $this->note,
                'return_reason'=>$orderCached['return_reason'],
                'order_total' => $this->final_total,
                'final_refunded' => $this->final_refunded,
                //'items' => OrderCacheItemResource::collection($orderCached['items']),
                'items' => OrderReturnItemResource::collection($this->items->where('qty_shipped', '>', 0)->where('qty_returned','>',0)),
        ];

    }

}