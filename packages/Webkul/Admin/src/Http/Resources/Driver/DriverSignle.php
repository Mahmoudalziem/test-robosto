<?php

namespace Webkul\Admin\Http\Resources\Driver;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderLogsActual;


class DriverSignle extends JsonResource
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

            if($order->status == Order::STATUS_DELIVERED && $order->actualLogs->isNotEmpty()  ){
                $orderConfirmedAt =$order->actualLogs->where('log_type', OrderLogsActual::ORDER_DRIVER_ITEMS_CONFIRMED)->first();
                $orderDeliveredAt =$order->actualLogs->where('log_type', OrderLogsActual::ORDER_DRIVER_ITEMS_DELIVERED)->first();
                $min= $orderDeliveredAt && $orderConfirmedAt?Carbon::parse($orderDeliveredAt->log_time)->diffInSeconds(Carbon::parse($orderConfirmedAt->log_time)):0;
                $orderTime=$orderTime+$min;
                $orderCount ++;
            }

        }
        $avgDeliveryTime=$orderTime>0?$orderTime/$orderCount:0;
        $seconds = $avgDeliveryTime % 60;
        $time = ($avgDeliveryTime - $seconds) / 60;
        $minutes = $time % 60;
        $hours = ($time - $minutes) / 60;
        $avgDeliveryTime = sprintf("%02d",$hours).":".sprintf("%02d",$minutes).":".sprintf("%02d",$seconds);
        return [
            'id'            => $this->id,
            'area_id'         => $this->area_id,
            'area'         => $this->area->name,
            'warehouse_id'         => $this->warehouse_id,
            'warehouse'         => $this->warehouse->name,
            'image'         => $this->image_url(),
            'image_id'         => $this->imageIdUrl(),
            'name'          => $this->name,
            'id_number'         => $this->id_number,
            'username'         => $this->username,
            'email'         => $this->email,
            'address' => $this->address,
            'phone_private' => $this->phone_private,
            'phone_work'    => $this->phone_work,
            'supervisor_rate' => $this->supervisor_rate,
            'liecese_validity_date'    => $this->liecese_validity_date,
            'license_plate_no'    => $this->motors->first()?$this->motors->first()->license_plate_no:null ,
            'availability'=>$this->availability,
            'is_online'=>$this->is_online,
            'status'        => $this->status,
            'avg_delivery_time'        => $avgDeliveryTime , //mins
            'wallet'        => $this->wallet,
            'total_wallet'=>$this->total_wallet,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}