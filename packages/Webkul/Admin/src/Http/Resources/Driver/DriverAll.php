<?php

namespace Webkul\Admin\Http\Resources\Driver;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Webkul\Sales\Models\OrderLogsActual;

class DriverAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request) {

        return $this->collection->map(function ($driver) {
              $avgDeliveryTime = '00:00:00';
//            $orderCount=0;
//            $orderTime=0; // mins
//            $actualOrderTime=0;
//            foreach( $driver->orders->where('status','delivered') as $order){
//
//                if($order->actualLogs->isNotEmpty()) {
//
//                    $orderConfirmedAt =$order->actualLogs->where('log_type', OrderLogsActual::ORDER_DRIVER_ITEMS_CONFIRMED)->first();
//                    $orderDeliveredAt =$order->actualLogs->where('log_type', OrderLogsActual::ORDER_DRIVER_ITEMS_DELIVERED)->first();
//                    $actualOrderDeliveryTime= isset($orderDeliveredAt->log_time) && isset($orderConfirmedAt->log_time) ?Carbon::parse($orderDeliveredAt->log_time)->diffInSeconds(Carbon::parse($orderConfirmedAt->log_time)):0;
//                    $actualOrderTime=$actualOrderTime+$actualOrderDeliveryTime;
//                }
//
//                $orderCount ++;
//            }
//
//            $actualOrderTime=$actualOrderTime>0?$actualOrderTime/$orderCount:0;
//            $seconds = $actualOrderTime % 60;
//            $time = ($actualOrderTime - $seconds) / 60;
//            $minutes = $time % 60;
//            $hours = ($time - $minutes) / 60;
//            $avgDeliveryTime = sprintf("%02d",$hours).":".sprintf("%02d",$minutes).":".sprintf("%02d",$seconds);

                    return [
                'id' => $driver->id,
                'area_id' => $driver->area_id,
                'area' => $driver->area->name,
                'warehouse_id' => $driver->warehouse_id,
                'warehouse' => $driver->warehouse->name,
                'id_number' => $driver->id_number,
                'username' => $driver->username,
                'email' => $driver->email,
                'image' => $driver->image_url(),
                'image_id' => $driver->imageIdUrl(),
                'name' => $driver->name,
                'address' => $driver->address,
                'phone_private' => $driver->phone_private,
                'phone_work' => $driver->phone_work,
                'supervisor_rate' => $driver->supervisor_rate,
                'liecese_validity_date' => $driver->liecese_validity_date,
                'license_plate_no' => $driver->motors->first() ? $driver->motors->first()->license_plate_no : null,
                'availability' => $driver->availability,
                'wallet' => (float) $driver->wallet,
                'total_wallet' => $driver->total_wallet,
                'avg_delivery_time' => $avgDeliveryTime ?? '00:00:00', //mins
                'status' => $driver->status,
                'is_onilne' => $driver->is_online == 1 ? 'Online' : 'Offline',
                'created_at' => $driver->created_at,
                'updated_at' => $driver->updated_at,
                    ];
                });
    }

}
