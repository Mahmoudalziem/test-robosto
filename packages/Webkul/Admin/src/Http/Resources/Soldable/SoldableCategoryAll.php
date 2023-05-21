<?php

namespace Webkul\Admin\Http\Resources\Soldable;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Webkul\Sales\Models\OrderLogsActual;

class SoldableCategoryAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request) {

        return $this->collection->map(function ($category) {

                    return [
                            'id' => $category->soldable->id,
                            'position' => $category->soldable->position,
                            'sold_count' => $category->sold_count,
                            "thumb" => $category->soldable->thumb,
                            'image' => $category->soldable->image,
                            'status' => $category->soldable->status,
                            'created_at' => $category->created_at,
                            'updated_at' => $category->updated_at,
                            "image_url" => $category->soldable->image_url,
                            'thumb_url' => $category->soldable->thumb_url,
                            'name' => $category->soldable->name,
                            'translation' => $category->soldable->translation,
                    ];
                });
    }

}
