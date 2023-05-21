<?php

namespace Webkul\Admin\Http\Resources\Sales;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OnlineCollectors extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($collector) {
            return [
                'id'                    => $collector->id,
                'name'                  => $collector->name
            ];
        });
    }

}
