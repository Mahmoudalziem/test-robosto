<?php

namespace Webkul\Admin\Http\Resources\Collector;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Sales\Models\OrderLogsActual;


class CollectorAvgPreparingTime extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $orderCount=0;
        $orderTime=0; // mins

        foreach( $this->orders as $order){

            $orderPreparedAt=$order->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_PREPARED)->first();
            $orderPreparingAt=$order->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_PREPARING)->first();

            $iSeconds= $orderPreparedAt &&  $orderPreparingAt ?
                ( $orderPreparedAt->log_time ? strtotime($orderPreparedAt->log_time):0)
                -
                ($orderPreparingAt->log_time ? strtotime($orderPreparingAt->log_time):0)
                : 0;
            $min = intval($iSeconds / 60);
            $orderTime=$orderTime+$min;
            $orderCount ++;
        }
        $avgPreparingTime=$orderTime>0?$orderTime/$orderCount:0;;

        return [
             'avg_preparing_time'=>$avgPreparingTime,
         ];
    }
}