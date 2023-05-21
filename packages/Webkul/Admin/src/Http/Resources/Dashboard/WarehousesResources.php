<?php

namespace Webkul\Admin\Http\Resources\Dashboard;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WarehousesResources extends CustomResourceCollection
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
                'lat'            => (float) $warehouse->latitude,
                'lng'            => (float) $warehouse->longitude,
            ];
        });
    }

}
