<?php

namespace Webkul\Admin\Http\Resources\Discount;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\User\Models\Admin;

class DiscountSingle extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {

        $productHasLabel=false;
        if($this->product->label){
             $productHasLabel=true;
        }
         
        return [
            "id" => $this->id,
            "discount_type" => $this->discount_type,
            "discount_value" => $this->discount_value,
            "area_id" => $this->areas->map->only(['id', 'name' ]),
            "product_id" => $this->product_id,
            "product" => $this->product,
            "orginal_price" => $this->product->price,
            "discount_price" => $this->discount_price,
            'discount_qty' => $this->discount_qty,
            "start_validity" => $this->start_validity,
            "end_validity" => $this->end_validity,
            'productHasLabel'=>$productHasLabel,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

}
