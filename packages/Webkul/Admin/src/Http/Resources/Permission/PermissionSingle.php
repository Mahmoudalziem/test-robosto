<?php

namespace Webkul\Admin\Http\Resources\Permission;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;


class PermissionSingle extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {


        return [
            "id" => $this->id,
            "slug" => $this->slug,
            "name" => $this->name,
            "permissions" => '',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

}
