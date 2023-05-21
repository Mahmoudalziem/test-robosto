<?php

namespace Webkul\Admin\Http\Resources\Promotion;

use App\Http\Resources\CustomResourceCollection;

class PromotionCustomers extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($promotion) {
            return [
                'customer_id'   => $promotion->customer->id,
                'name'          => $promotion->customer->name,
                'avatar_url'    => $promotion->customer->avatar_url
            ];
        });
    }
}
