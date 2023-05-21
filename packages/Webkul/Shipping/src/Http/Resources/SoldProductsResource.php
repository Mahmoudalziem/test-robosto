<?php

namespace Webkul\Shipping\Http\Resources;

use App\Http\Resources\CustomResourceCollection;

class SoldProductsResource extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($item) {
            $product = $item->item;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit' => $product->unit->name,
                'unit_value' => $product->unit_value,
                'total_sold' => $item->total_sold,
            ];
        });
    }
}
