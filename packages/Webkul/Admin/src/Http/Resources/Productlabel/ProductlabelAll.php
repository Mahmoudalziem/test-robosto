<?php

namespace Webkul\Admin\Http\Resources\Productlabel;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;

class ProductlabelAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request) {

        return $this->collection->map(function ($productlabel) {

                    return [
                "id" => $productlabel->id,
                "name" => $productlabel->name,
                "name_ar" => $productlabel->translate('ar')->name,
                "name_en" => $productlabel->translate('en')->name,                        
                "status" => $productlabel->status,
                'created_at' => $productlabel->created_at,
                'updated_at' => $productlabel->updated_at,
                    ];
                });
    }

}
