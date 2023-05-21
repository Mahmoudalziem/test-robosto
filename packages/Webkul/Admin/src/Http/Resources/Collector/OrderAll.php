<?php

namespace Webkul\Admin\Http\Resources\Collector;


use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

            // estimated time
            $estimatedOrderPreparedTimeSeconds=$order->estimatedLogs->where('log_type', OrderLogsEstimated::PREPARING_TIME)->first();

            if($estimatedOrderPreparedTimeSeconds){
                $estimatedOrderPreparationTime =$estimatedOrderPreparedTimeSeconds->log_time >0?  $estimatedOrderPreparedTimeSeconds->log_time  :0;
                $seconds = $estimatedOrderPreparationTime % 60;
                $time = ($estimatedOrderPreparationTime - $seconds) / 60;
                $minutes = $time % 60;
                $hours = ($time - $minutes) / 60;
                $estimatedOrderPreparationTime = sprintf("%02d",$hours).":".sprintf("%02d",$minutes).":".sprintf("%02d",$seconds);
            }
            else{
                $estimatedOrderPreparationTime=0;
            }


            // actual time
            $actualOrderPreparedAt=$order->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_PREPARED)->first();
            $actualOrderPreparingAt=$order->actualLogs->where('log_type', OrderLogsActual::ORDER_ITEMS_PREPARING)->first();
            if($actualOrderPreparedAt && $actualOrderPreparingAt){
                $actualOrderPreparationTime= $actualOrderPreparedAt->log_time?Carbon::parse($actualOrderPreparedAt->log_time)->diffInSeconds(Carbon::parse($actualOrderPreparingAt->log_time)):0;
                $seconds = $actualOrderPreparationTime % 60;
                $time = ($actualOrderPreparationTime - $seconds) / 60;
                $minutes = $time % 60;
                $hours = ($time - $minutes) / 60;
                $actualOrderPreparationTime = sprintf("%02d",$hours).":".sprintf("%02d",$minutes).":".sprintf("%02d",$seconds);
            }  else{
                $actualOrderPreparationTime=0;
            }


            return [
                'id' => $order->id,
                'increment_id' => $order->increment_id,
                'status' => $order->status,
                'status_name' => $order->status_name,
                'order_date' => Carbon::parse($order->created_at)->toDateTimeString(),
                'estimatedOrderPreparationTime'=>$estimatedOrderPreparationTime,
                'actualOrderPreparationTime'=>$actualOrderPreparationTime,
            ];
        });
    }

}