<?php

namespace Webkul\Admin\Http\Resources\Sales;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductsSearch extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($product) {
            return [
                'id'                    => $product->id,
                'image_url'             => $product->image_url,
                'thumb_url'             => $product->thumb_url,
                'price'                 => $product->price,
                'unit_name'             => $product->unit->name,
                'unit_value'            => $product->unit_value,
                'name'                  => $product->name,
                'total_in_stock'        => $product->total_in_stock,
            ];
        });
    }

}
