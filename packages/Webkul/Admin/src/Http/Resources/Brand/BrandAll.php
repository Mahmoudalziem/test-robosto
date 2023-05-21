<?php

namespace Webkul\Admin\Http\Resources\Brand;

use App\Http\Resources\CustomResourceCollection;


class BrandAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {


        return $this->collection->map(function ($brand) {


            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'prefix' => $brand->prefix,
                'image' => $brand->image,
                'image_url' => $brand->image_url,
                'status' => $brand->status,
                'products' => $brand->products ? new BrandProducts($brand->products()->limit(3)->get()) : null,
                'created_at' => $brand->created_at,
            ];
        });
    }

}