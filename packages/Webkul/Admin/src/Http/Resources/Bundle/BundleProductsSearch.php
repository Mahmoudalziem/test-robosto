<?php

namespace Webkul\Admin\Http\Resources\Bundle;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Webkul\Admin\Http\Resources\Area\Area;
use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class BundleProductsSearch extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request) {
        return $this->collection->map(function ($product) {
                    return [
                'id' => $product->id,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'price'  => $product->price,                        
                'image' => $product->image,
                'image_url' => $product->image_url,
                'total_in_stock' => $product->total_in_stock,
                    ];
                });
    }

}
