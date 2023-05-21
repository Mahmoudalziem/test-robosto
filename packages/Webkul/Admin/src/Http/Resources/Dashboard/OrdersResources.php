<?php

namespace Webkul\Admin\Http\Resources\Dashboard;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OrdersResources extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($order) {
            return [
                'id'            => $order->id,
                'lat'            => (float) $order->address->latitude,
                'lng'            => (float) $order->address->longitude,
            ];
        });
    }

}
