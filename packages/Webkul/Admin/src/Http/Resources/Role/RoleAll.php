<?php

namespace Webkul\Admin\Http\Resources\Role;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;

class RoleAll extends CustomResourceCollection {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request) {

        return $this->collection->map(function ($role) {

                    return [
                "id" => $role->id,
                "slug" => $role->slug,
                "name" => $role->name,
                "permissions" =>  $role->permissions->pluck('name','route_name'),
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
                    ];
                });
    }

}
