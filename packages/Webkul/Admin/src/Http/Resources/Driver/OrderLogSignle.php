<?php

namespace Webkul\Admin\Http\Resources\Driver;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Webkul\Sales\Models\OrderLogsActual;
use Webkul\Sales\Models\OrderLogsEstimated;

class OrderLogSignle extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return Collection
     */
    public function toArray($request) {

        $estimatedOrderDeliveryTime = '';
        $actualOrderDeliveryTime = '';

        // estimated time (delivery time)
        if ($this->estimatedLogs->isNotEmpty()) {
            $estimatedOrderDeliveryTimeSeconds = $this->estimatedLogs->where('log_type', OrderLogsEstimated::DELIVERY_TIME)->first();
            if ($estimatedOrderDeliveryTimeSeconds) {
                $estimatedOrderDeliveryTime = $estimatedOrderDeliveryTimeSeconds->log_time > 0 ? intval($estimatedOrderDeliveryTimeSeconds->log_time / 60) : 0;
                $seconds = $estimatedOrderDeliveryTime % 60;
                $time = ($estimatedOrderDeliveryTime - $seconds) / 60;
                $minutes = $time % 60;
                $hours = ($time - $minutes) / 60;
                $estimatedOrderDeliveryTime = sprintf("%02d", $hours) . ":" . sprintf("%02d", $minutes) . ":" . sprintf("%02d", $seconds);
            }
        }

        // actual time
        if ($this->actualLogs->isNotEmpty()) {
            $actualOrderDeliveredAt = $this->actualLogs->where('log_type', OrderLogsActual::ORDER_DRIVER_ITEMS_DELIVERED)->first();
            $actualOrderConfirmedAt = $this->actualLogs->where('log_type', OrderLogsActual::ORDER_DRIVER_ITEMS_CONFIRMED)->first();
            $actualOrderDeliveredAtLogTime = isset($actualOrderDeliveredAt->log_time) ? $actualOrderDeliveredAt->log_time : null;
            $actualOrderConfirmedAtLogTime = isset($actualOrderConfirmedAt->log_time) ? $actualOrderConfirmedAt->log_time : null;
            $actualOrderDeliveryTime = $actualOrderDeliveredAtLogTime ? Carbon::parse($actualOrderDeliveredAtLogTime)->diffInSeconds(Carbon::parse($actualOrderConfirmedAtLogTime)) : 0;
            $seconds = $actualOrderDeliveryTime % 60;
            $time = ($actualOrderDeliveryTime - $seconds) / 60;
            $minutes = $time % 60;
            $hours = ($time - $minutes) / 60;
            $actualOrderDeliveryTime = sprintf("%02d", $hours) . ":" . sprintf("%02d", $minutes) . ":" . sprintf("%02d", $seconds);
        }

        
        $orderLabels['has_notes'] = $this->notes->count() > 0 ? true : false;
        $orderLabels['has_feedback'] = $this->comment()->count() > 0 ? true : false;

        return [
            'id' => $this->increment_id,
            'order_id' => $this->id,
            'status' => $this->status,
            'status_name' => $this->status_name,
            'order_date' => Carbon::parse($this->created_at)->format('d M Y H:i:s a'),
            'customer_name' => $this->customer->name,
            'address' => $this->address ? $this->address->address : null,
            'order_price' => $this->sub_total + $this->delivery_chargs + $this->tax_amount,
            'amount_to_pay' => $this->final_total,
            'request_status' => "Accepted",
            'cancellation_reason' => null,
            'order_lables'=>$orderLabels,            
            //'estimatedOrderDeliveryTime'=>$estimatedOrderDeliveryTime,
            //'actualOrderDeliveryTime'=>$actualOrderDeliveryTime,
            'estimatedOrderDeliveryTime' => Carbon::parse($this->created_at)->addSeconds($this->expected_delivered_date)->format('d M Y H:i:s a'),
            'actualOrderDeliveryTime' => $this->delivered_at ? Carbon::parse($this->delivered_at)->format('d M Y H:i:s a') : null
        ];
    }

}
