<?php

namespace Webkul\Collector\Http\Resources\Order;

use Illuminate\Http\Request;
use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;


class OrderItemSkuResource extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($sku) {

            return [
                'sku'            => $sku->sku,
                'qty'         => $sku->qty
            ];
        });
    }

}