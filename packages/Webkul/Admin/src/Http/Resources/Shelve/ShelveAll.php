<?php

namespace Webkul\Admin\Http\Resources\Shelve;

use App\Http\Resources\CustomResourceCollection;

class ShelveAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {

        return  $this->collection->map(function ($shelve) {

            return [
                'id'            => $shelve->id,
                'name'          => $shelve->name . $shelve->row,
                'row'           => $shelve->row,
                'position'      => $shelve->position,
                'products'      => new Products($shelve->products),
            ];
        });
    }

}