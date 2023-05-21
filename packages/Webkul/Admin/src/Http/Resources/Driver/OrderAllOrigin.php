<?php

namespace Webkul\Admin\Http\Resources\Driver;


use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Webkul\Sales\Models\OrderDriverDispatch;
use Webkul\Sales\Models\OrderLogsActual;
use Webkul\Sales\Models\OrderLogsEstimated;

class OrderAllDelivered extends CustomResourceCollection
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

            $estimatedOrderDeliveryTime='';
            $actualOrderDeliveryTime='';

            $orderDriverDispatch=OrderDriverDispatch::where([ 'order_id'=>$order->id,'driver_id'=>$order->driver_id])->orderBy('id','desc')->get();
            if($orderDriverDispatch->isNotEmpty()){
                $driverCancellationReason=$orderDriverDispatch->first()->reason;
                $requestStatus=$order->id && $orderDriverDispatch->first()->status != 'cancelled'?'Accepted':$orderDriverDispatch->first()->status;
            }else{
                $driverCancellationReason='';
                $requestStatus='';
            }

            // estimated time (delivery time)
            if($order->estimatedLogs->isNotEmpty()){
                $estimatedOrderDeliveryTimeSeconds=$order->estimatedLogs->where('log_type', OrderLogsEstimated::DELIVERY_TIME)->first();
                if($estimatedOrderDeliveryTimeSeconds){
                    $estimatedOrderDeliveryTime =$estimatedOrderDeliveryTimeSeconds->log_time >0? intval( $estimatedOrderDeliveryTimeSeconds->log_time / 60):0;
                }
            }

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
                'id' => $order->id,
                'status' => $order->status,
                'status_name' => $order->status_name,
                'order_date' => $order->created_at,
                'request_status'=> $requestStatus,
                'cancellation_reason'=>$driverCancellationReason,
                'estimatedOrderDeliveryTime'=>$estimatedOrderDeliveryTime,
                'actualOrderDeliveryTime'=>$actualOrderDeliveryTime,
            ];
        });
    }

}