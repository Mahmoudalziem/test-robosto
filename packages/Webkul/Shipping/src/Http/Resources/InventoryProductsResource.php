<?php

namespace Webkul\Shipping\Http\Resources;

use App\Http\Resources\CustomResourceCollection;

class InventoryProductsResource extends CustomResourceCollection
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
            $product = $item->product;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit' => $product->unit->name,
                'unit_value' => $product->unit_value,
                'exp_date' => $item->exp_date ?? null,
                'qty' => $item->qty ?? null,
                'total_qty' => $item->total_qty ?? null,
            ];
        });
    }
}
