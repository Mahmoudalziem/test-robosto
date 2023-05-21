<?php

namespace Webkul\Admin\Http\Resources\Brand;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;


class BrandProducts extends CustomResourceCollection
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
                'barcode'            => $product->barcode,
                'prefix'            => $product->prefix,
                'image'            => $product->image,
                'image_url'            => $product->image_url,
                'thumb_url'            => $product->thumb_url,
                'featured'            => $product->featured,
                'status'            => $product->status,
                'returnable'            => $product->returnable,
                'price'            => $product->price,
                'cost'            => $product->cost,
                'tax'            => $product->tax,
                'weight'            => $product->weight,
                'width'            => $product->width,
                'height'            => $product->height,
                'length'            => $product->length,
                'brand_id'            => $product->brand_id,
                'unit_id'            => $product->unit_id,
                'unit_value'            => $product->unit_value,
                'unit_name'         => $product->unit->name,
                'name'         => $product->name,
                'shelve'         => $product->shelve,
                'unit' => $product->unit,
                'brand' => $product->brand,
                'suppliers' => $product->suppliers,
                'areas' => $product->areas,
                'warehouses' => $product->warehouses,
                'inventoryProducts' => $product->inventoryProducts,
                'subCategories' => $product->subCategories,
                'created_at'    => $product->created_at,
                'updated_at'    => $product->updated_at,
            ];
        });
    }

}