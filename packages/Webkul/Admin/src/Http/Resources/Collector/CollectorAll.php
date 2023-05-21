<?php

namespace Webkul\Admin\Http\Resources\Collector;

use App\Http\Resources\CustomResourceCollection;
use Webkul\Sales\Models\OrderLogsActual;

class CollectorAll extends CustomResourceCollection
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {

        return $this->collection->map(function ($collector) {
            $orderCount = 0;
            $orderTime = 0; // mins
            $avgPreparingTime = 0;
            //                    foreach ($collector->orders as $order) {
            //
            //                        $orderPreparedAt = $order->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_PREPARED)->first();
            //                        $orderPreparingAt = $order->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_PREPARING)->first();
            //
            //                        $iSeconds = $orderPreparedAt && $orderPreparingAt ?
            //                                ($orderPreparedAt->log_time ? strtotime($orderPreparedAt->log_time) : 0) -
            //                                ($orderPreparingAt->log_time ? strtotime($orderPreparingAt->log_time) : 0) : 0;
            //                        $min = intval($iSeconds / 60);
            //                        $orderTime = $orderTime + $min;
            //                        $orderCount++;
            //                    }
            //                    $avgPreparingTime = $orderTime > 0 ? $orderTime / $orderCount : 0;
            //
            return [
                'id' => $collector->id,
                'area_id' => $collector->area_id,
                'area' => $collector->area->name,
                'warehouse_id' => $collector->warehouse_id,
                'warehouse' => $collector->warehouse->name,
                'image' => $collector->image_url(),
                'image_id' => $collector->imageIdUrl(),
                'id_number' => $collector->id_number,
                'name' => $collector->name,
                'username' => $collector->username,
                'email' => $collector->email,
                'address' => $collector->address,
                'phone_private' => $collector->phone_private,
                'phone_work' => $collector->phone_work,
                'availability' => $collector->availability,
                'avg_preparing_time' => $avgPreparingTime,
                'orders' => [],
                'logs' => [],
                'status' => $collector->status,
                'is_onilne' => $collector->is_online == 1 ? 'Online' : 'Offline',
                'can_receive_orders' => $collector->can_receive_orders == '0' ? false : true,
                'created_at' => $collector->created_at,
                'updated_at' => $collector->updated_at,
            ];
        });
    }
}