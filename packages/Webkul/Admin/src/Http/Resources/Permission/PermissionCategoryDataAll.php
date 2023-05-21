<?php

namespace Webkul\Admin\Http\Resources\Permission;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;

class PermissionCategoryDataAll extends CustomResourceCollection
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {

        return $this->collection->map(function ($permissionCategory) {
            
            $response = [
                "id" => $permissionCategory->slug,
                "slug" => $permissionCategory->slug,
                "label" => $permissionCategory->name,
            ];

            if (count($permissionCategory->children) > 0) {
                $response['children'] = new PermissionCategoryDataAll($permissionCategory->children);

            } else {
                $response['children'] = PermissionDataResource::collection($permissionCategory->permissions);
            }

            return $response;
        });
    }
}
