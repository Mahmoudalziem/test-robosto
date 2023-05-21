<?php

namespace Webkul\Admin\Http\Resources\Product;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductSKUs extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($sku) {
            return [
                "sku"                       => $sku->sku,
                "prod_date"                 => $sku->prod_date,
                "exp_date"                  => $sku->exp_date,
                "cost"                      => $sku->cost,
                "warehouse"                 => $sku->warehouse->name,
                "price"                     => $sku->price,
                "amount"                    => $sku->amount,
                "amount_before_discount"    => $sku->amount_before_discount,
                "area_id"                   => $sku->area_id,
                "cost_before_discount"      => $sku->cost_before_discount,
                "id"                        => $sku->id,
                "product_id"                => $sku->product_id,
                "qty"                       => $sku->qty,
                "warehouse_id"              => $sku->warehouse_id,
            ];
        });
    }
}
