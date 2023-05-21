<?php

namespace Webkul\Admin\Http\Resources\Category;

use App\Http\Resources\CustomResourceCollection;

class SubCategoryAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {

        return  $this->collection->map(function ($subCategory) {

            return [
                'id'            => $subCategory->id,
                'name'         => $subCategory->name,
                'position'         => $subCategory->position,
                'sold_count'         => $subCategory->sold_count,
                'image'         => $subCategory->image,
                'image_url'         => $subCategory->image_url,
                'status'         => $subCategory->status,
                'parent_categories' => (array)    null !== $subCategory->parentCategories() ?$subCategory->parentCategories  :'-' ,
                'translations'         => $subCategory->translations,
                'created_at'         => $subCategory->created_at,
                'updated_at'         => $subCategory->updated_at,

            ];
        });
    }

}