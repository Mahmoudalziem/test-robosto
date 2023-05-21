<?php

namespace Webkul\Collector\Http\Resources\Inventory;

use App\Http\Resources\CustomResourceCollection;

class ProductAll extends CustomResourceCollection
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
                'product_id'     => $product->product_id ,
                'product_name'    => $product->product->name,
                'qty'            => (int)  number_format($product->qty, 0) ,
                'unit'            => $product->product->unit->name,
                'unit_value' => $product->product->unit_value,
                'barcode' => $product->product->barcode,                   
                'image'             =>$product->product->image_url ,
                'thumb_url'         => $product->product->thumb_url,
            ];

        });
    }

}