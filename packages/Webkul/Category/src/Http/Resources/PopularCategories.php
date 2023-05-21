<?php

namespace Webkul\Category\Http\Resources;

use App\Http\Resources\CustomResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PopularCategories extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($category) {

            return [
                'id'                    => $category->id,
                'image_url'             => $category->image_url,
                'thumb_url'             => $category->thumb_url,
                'name'                  => $category->name,
                'description'           => $category->description,
            ];
        });
    }

}
