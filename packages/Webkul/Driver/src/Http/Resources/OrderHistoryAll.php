<?php

namespace Webkul\Driver\Http\Resources;


use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

use Webkul\Sales\Models\OrderLogsActual;


class OrderHistoryAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {


        return $this->collection->map(function ($order) {

                  // actual time
                $actualOrderDeliveredAt=$order->actualLogs->where('log_type', OrderLogsActual::ORDER_DRIVER_ITEMS_DELIVERED)->first();
                $actualOrderPreparedAt=$order->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_PREPARED)->first();
                $actualSeconds= $actualOrderDeliveredAt && $actualOrderPreparedAt ?
                    ( $actualOrderDeliveredAt->log_time ? strtotime($actualOrderDeliveredAt->log_time):0)
                    -
                    ($actualOrderPreparedAt->log_time ? strtotime($actualOrderPreparedAt->log_time):0)
                    : 0;
                $actualOrderDeliveryTime = intval($actualSeconds / 60);

            return [
                'id' => $order->increment_id,
                'status' => $order->status,
                'status_name' => $order->status_name,
                'order_date' => $order->created_at,
                'customer_name' => $order->customer->name,
                'final_total' => $order->final_total,
                'actualOrderDeliveryTime'=>$actualOrderDeliveryTime,
            ];
        });
    }

}