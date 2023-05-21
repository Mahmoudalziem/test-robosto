<?php

namespace Webkul\Admin\Http\Resources\Driver;


use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Webkul\Sales\Models\OrderDriverDispatch;
use Webkul\Sales\Models\OrderLogsActual;
use Webkul\Sales\Models\OrderLogsEstimated;

class OrderAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return Collection
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($order) {

             return [
                'increment_id' => $order->event_properties['orderIncrementId'] ?? null,                 
                'order_id' => $order->event_properties['orderId'] ?? null,
                'driver_written_price' => $order->event_properties['amount'] ?? null,
            ];
        });
    }

}