<?php

namespace Webkul\Admin\Http\Resources\Shelve;

use App\Http\Resources\CustomResourceCollection;

class Products extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {

        return  $this->collection->map(function ($product) {

            return [
                'id'            => $product->id,
                'name'         => $product->name,
                'image_url'         => $product->image_url,
            ];
        });
    }

}