<?php

namespace Webkul\Admin\Http\Resources\Supplier;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SupplierProducts extends CustomResourceCollection
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
                'id'            => $product->id,
                'brand_name'         => $product->brand->name,
                'brand_image_url'         => $product->brand->image_url,
                'product_id'         => $product->product->id,
                'product_name'         => $product->product->name,
                'product_image_url'         => $product->product->image_url,
                'selling'         => $product->product->price,
            ];
        });
    }

}