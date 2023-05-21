<?php

namespace Webkul\Bundle\Http\Resources;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BundleAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($bundle) {

            return [
                'id'            => $bundle->id,
                'image_url'            => $bundle->image_url,
                'thumb_url'            => $bundle->thumb_url,
                'price'            => $bundle->price,
                'barcode'            => $bundle->barcode,
                'tax'            => $bundle->tax,
                'weight'            => $bundle->weight,
                'width'            => $bundle->width,
                'height'            => $bundle->height,
                'length'            => $bundle->length,
                'unit_name' => $bundle->unit->name,
                'unit_value'            => $bundle->unit_value,
                'name'         => $bundle->name,
                'description'         => $bundle->description,
                'total_in_stock'         => $bundle->total_in_stock,
            ];
        });
    }

}
