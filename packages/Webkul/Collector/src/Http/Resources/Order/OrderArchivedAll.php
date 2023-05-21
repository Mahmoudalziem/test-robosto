<?php

namespace Webkul\Collector\Http\Resources\Order;

use App\Http\Resources\CustomResourceCollection;

class OrderArchivedAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {

        return  $this->collection->map(function ($order) {


            return [
                'id'            => $order->id,
                'preparing_at'         => $order->preparing_at,
                'prepared_time'          => $order->prepared_time,


            ];
          //  0 => Cancelled, 1 => Pending, 2 => on-the-way, 3 => transferred
        });
    }

}