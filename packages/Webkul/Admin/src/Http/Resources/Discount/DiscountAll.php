<?php

namespace Webkul\Admin\Http\Resources\Discount;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;

class DiscountAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request) {

        return $this->collection->map(function ($discount) {

                    return [
                "id" => $discount->id,
                "discount_type" => $discount->discount_type,
                "discount_value" => $discount->discount_value,
                "area_id" =>  $discount->areas->map->only(['id', 'name' ])   ,
                "product_id" => $discount->product_id,
                "product" => $discount->product,
                "orginal_price" => $discount->orginal_price,
                "discount_price" => $discount->discount_price,
                'discount_qty' => $discount->discount_qty,
                "start_validity" => $discount->start_validity,
                "end_validity" => $discount->end_validity,
                'created_at' => $discount->created_at,
                'updated_at' => $discount->updated_at,
                    ];
                });
    }

}
