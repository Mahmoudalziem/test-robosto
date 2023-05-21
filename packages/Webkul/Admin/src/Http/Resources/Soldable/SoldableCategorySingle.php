<?php

namespace Webkul\Admin\Http\Resources\Soldable;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\User\Models\Admin;

class SoldableCategorySingle extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {



        $rolePermissions = $this->rolePermissions();
//        $validPermissions = [];
//        $validPermissionsName = '';
//        foreach ($rolePermissions as $permission) {
//            $obj = explode('.', $permission);
//            $length = count($obj);
//            $action = $obj[$length - 1];
//            if ($action != 'store' && $action != 'update' && $action != 'show' && $action != 'delete' && $action != 'update-status') {
//                $permission = array_slice($obj, 1, -1);
//                $validPermissionsName = implode(".", $permission);
//                $validPermissions[] = $validPermissionsName;
//            }
//        }
//
//        $validPermissions = array_unique($validPermissions);
        $defaultRoute = '';
        if (count($rolePermissions) >0) {
            $default = $rolePermissions[0];
            $obj = explode('.', $default);
            $removedFirstLast = array_slice($obj, 1, -1);
            $defaultRoute = implode(".", $removedFirstLast);
        }


        return [
            "id" => $this->id,
            "name" => $this->name,
            "email" => $this->email,
            "username" => $this->username,
            "id_number" => $this->id_number,
            "is_verified" => $this->is_verified,
            "status" => $this->status,
            "address" => $this->address,
            "phone_work" => $this->phone_work,
            "phone_private" => $this->phone_private,
            "image" => $this->image_url,
            "areas" => $this->areas->map(function ($area) {
                return ['id' => $area['id'], 'name' => $area['name']];
            }),
            "warehouses" => $this->warehouses->map(function ($warehouses) {
                return ['id' => $warehouses['id'], 'name' => $warehouses['name']];
            }),
            "roleNames" => $this->getRoleNames(),
            "roles" => $this->roles->pluck('id')->toArray(),
            "permissionNames" => $this->getRolePermissionNames(),
            "permissions" => $this->rolePermissions(),
            'default_route' => $defaultRoute,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

}
