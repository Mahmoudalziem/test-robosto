<?php

namespace Webkul\Admin\Http\Resources\Customer;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CustomerDevicesAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return Collection
     */
    public function toArray($request) {


        return $this->collection->map(function ($device) {

                    return [
                'deviceid' => $device->deviceid,
                'customer_id' => $device->customer_id,
                'customer_name' => $device->customer->name,
                'promo_code' => $device->promotion->promo_code,
                'increment_id' => $device->order->increment_id,
                'order_id' => $device->order->id,
                'order_date' => Carbon::parse($device->created_at)->format('d M Y h:i:s a'),
                    ];
                });
    }

}
