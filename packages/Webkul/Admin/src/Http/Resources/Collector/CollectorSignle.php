<?php

namespace Webkul\Admin\Http\Resources\Collector;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Sales\Models\OrderLogsActual;

class CollectorSignle extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $orderCount = 0;
        $orderTime = 0; // mins

        foreach ($this->orders as $order) {

            $orderPreparedAt = $order->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_PREPARED)->first();
            $orderPreparingAt = $order->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_PREPARING)->first();

            $iSeconds = $orderPreparedAt && $orderPreparingAt ?
                ($orderPreparedAt->log_time ? strtotime($orderPreparedAt->log_time) : 0) -
                ($orderPreparingAt->log_time ? strtotime($orderPreparingAt->log_time) : 0) : 0;
            $min = intval($iSeconds / 60);
            $orderTime = $orderTime + $min;
            $orderCount++;
        }
        $avgPreparingTime = $orderTime > 0 ? $orderTime / $orderCount : 0;;

        return [
            'id' => $this->id,
            'area_id' => $this->area_id,
            'area' => $this->area->name,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->warehouse->name,
            'image' => $this->image_url(),
            'image_id' => $this->imageIdUrl(),
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'id_number' => $this->id_number,
            'address' => $this->address,
            'phone_private' => $this->phone_private,
            'phone_work' => $this->phone_work,
            'availability' => $this->availability,
            'status' => $this->status,
            'is_online' => $this->is_online,
            'can_receive_orders' => $this->can_receive_orders == '0' ? false : true,
            'avg_preparing_time' => $avgPreparingTime,
            'orders' => [],
            'logs' => [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}