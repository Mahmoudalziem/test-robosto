<?php

namespace Webkul\Admin\Http\Resources\Inventory;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class InventoryAreaAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request) {


        return $this->collection->map(function ($invArea) {


                    return [
                'id' => $invArea->id,
                'barcode' => $invArea->product->barcode,
                'image' => $invArea->product->image,
                'image_url' => $invArea->product->image_url,
                'thumb_url' => $invArea->product->thumb_url,
                'minimum_stock' => $invArea->product->minimum_stock,
                'returnable' => $invArea->product->returnable,
                'price' => $invArea->product->price,
                'cost' => $invArea->product->cost,
                'name' => $invArea->product->name,
                'total_qty'=>$invArea->total_qty,
                //'unit' => $invArea->product->unit,
                //'brand' => $invArea->product->brand->name,

                //'subCategories' => $invArea->product->subCategories->pluck('name'),

                    ];
                });
    }

}
