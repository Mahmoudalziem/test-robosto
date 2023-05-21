<?php

namespace Webkul\Admin\Http\Resources\Product;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UnitAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {


        return  $this->collection->map(function ($unit) {


            return [
                'id'            => $unit->id,
                'name'         => $unit->name,
                'measure'            => $unit->measure,
                'status'            => $unit->status,
                'created_at'    => $unit->created_at,
                'updated_at'    => $unit->updated_at,
            ];
        });
    }

}
