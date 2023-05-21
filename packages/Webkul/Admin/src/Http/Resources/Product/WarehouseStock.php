<?php

namespace Webkul\Admin\Http\Resources\Product;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WarehouseStock extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {


        return  $this->collection->map(function ($warehouse) {


            return [
                'id'            => $warehouse->id,
                'name'            => $warehouse->name,
                'quantity'     => $warehouse->pivot->qty,
            ];
        });
    }

}
