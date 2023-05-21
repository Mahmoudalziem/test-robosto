<?php

namespace Webkul\Admin\Http\Resources\Category;

use App\Http\Resources\CustomResourceCollection;

class CategoriesAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($category) {

            return [
                'id'            => $category->id,
                'name'         => $category->name,
                'position'         => $category->position,
                'sold_count'         => $category->sold_count,
                'image'         => $category->image,
                'image_url'         => $category->image_url,
                'status'         => $category->status,
                'created_at'         => $category->created_at,
                'updated_at'         => $category->updated_at,
            ];
        });
    }

}