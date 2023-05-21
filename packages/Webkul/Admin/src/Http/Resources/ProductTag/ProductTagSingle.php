<?php

namespace Webkul\Admin\Http\Resources\ProductTag;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;


class ProductTagSingle extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {

        return [
            "id" => $this->id,
            "name" => $this->name,
            "translations" => $this->translations,            
            "status" => $this->status,            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

}
