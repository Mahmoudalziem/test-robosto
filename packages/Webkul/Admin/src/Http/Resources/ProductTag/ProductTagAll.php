<?php

namespace Webkul\Admin\Http\Resources\ProductTag;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;

class ProductTagAll extends CustomResourceCollection
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($productTag) {

            return [
                "id" => $productTag->id,
                "name" => $productTag->name,
                "name_ar" => $productTag->translate('ar')->name,
                "name_en" => $productTag->translate('en')->name,
                "status" => $productTag->status,
                'created_at' => $productTag->created_at,
                'updated_at' => $productTag->updated_at,
            ];
        });
    }
}
