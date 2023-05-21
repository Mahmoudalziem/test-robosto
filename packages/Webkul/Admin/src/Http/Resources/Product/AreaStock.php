<?php

namespace Webkul\Admin\Http\Resources\Product;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AreaStock extends CustomResourceCollection
{
    private $product;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource, $product)
    {
        parent::__construct($resource);

        $this->product = $product;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($area) {
            return [
                'id'            => $area->id,
                'name'            => $area->name,
                'quantity'     => $area->pivot->total_qty,
                'stores'         => new WarehouseStock($this->product->warehouses->where('area_id', $area->id)),
            ];
        });
    }
}
