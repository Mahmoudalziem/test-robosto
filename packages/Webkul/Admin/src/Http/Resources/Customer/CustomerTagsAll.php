<?php

namespace Webkul\Admin\Http\Resources\Customer;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CustomerTagsAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return Collection
     */
    public function toArray($request) {


        return $this->collection->map(function ($tag) {

            return [
                'id' => $tag->id,
                'name' => $tag->name
            ];
        });
    }

}
