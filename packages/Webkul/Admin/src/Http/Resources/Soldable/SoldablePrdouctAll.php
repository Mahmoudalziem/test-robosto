<?php

namespace Webkul\Admin\Http\Resources\Soldable;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Webkul\Sales\Models\OrderLogsActual;

class SoldablePrdouctAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request) {

        return $this->collection->map(function ($product) {

                    return [
                            'product_id' => $product->soldable->id,
                            'total_sold' => $product->sold_count,
                            'item' => $product->soldable,

                    ];
                });
    }

}
