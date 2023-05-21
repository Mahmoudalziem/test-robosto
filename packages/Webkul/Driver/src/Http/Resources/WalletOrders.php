<?php

namespace Webkul\Driver\Http\Resources;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WalletOrders extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($order) {
            return [
                'increment_id' => $order->increment_id,
                'price' => $order->final_total,
                'address' => $order->address->address,
            ];
        });
    }

}