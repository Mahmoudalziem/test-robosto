<?php

namespace Webkul\Admin\Http\Resources\Role;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;


class RoleSingle extends JsonResource {

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
            "permissions" =>  $this->permissions->pluck('name','route_name'),
            "permission_ids" =>  $this->permissions()->orderBy('permissions.id','asc')->pluck('permissions.id'),   
            'translations'         => $this->translations,            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

}
