<?php

namespace Webkul\Admin\Http\Resources\User;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\Config;

class AdminSingle extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {


        $sidebarMain = Config::get('permissions.sidebar_main');
        $sidebarMainRoutes = Config::get('permissions.sidebar_main_routes');

        $sidebarSub = Config::get('permissions.sidebar_sub');
        $sidebarSubRoutes = Config::get('permissions.sidebar_sub_routes');

        $mainPermissions = $this->roleMainPermissions();
        // $sidebarSubRoutes['app-management'] ;

        $filledMainPermissions = [];
        $filledSubPermissions = [];
        foreach ($sidebarMainRoutes as $sidebarMainRoute) {

            foreach ($mainPermissions as $mainPermission) {

                $permissionObj = explode('.', $mainPermission->route_name);

                if (isset($mainPermission->category->directParent->slug) && $mainPermission->category->directParent->slug == $sidebarMainRoute) {

                    if (!isset($filledMainPermissions[$sidebarMainRoute])) {
                        $filledMainPermissions[$sidebarMainRoute] = $sidebarMain[$sidebarMainRoute][explode('.', $mainPermission->route_name)[2]];
                    }
                }
            }
        }

        foreach ($sidebarSubRoutes as $sidebarSubRoute) {

            foreach ($mainPermissions as $mainPermission) {

                $permissionObj = explode('.', $mainPermission->route_name);

                if (isset($mainPermission->category->directParent->slug) && $mainPermission->category->directParent->slug == $sidebarSubRoute) {
                    if (!isset($filledSubPermissions[$sidebarSubRoute])) {
                        $subConfig = $sidebarSub[$sidebarSubRoute];
                        $filledSubPermissions[$sidebarSubRoute] = $sidebarSub[$sidebarSubRoute][$permissionObj[3]];
                    }
                }
            }
        }
 
 
        $rolePermissions = $this->rolePermissions();
        $validPermissions = [];
//      $validPermissionsName=[];
//        $validPermissionsName = '';
//        foreach ($rolePermissions as $k => $permission) {
//            $obj = explode('.', $permission);
//            $length = count($obj);
//            $action = $obj[$length - 1];
//            if ($action != 'store' && $action != 'update' && $action != 'show' && $action != 'delete' && $action != 'update-status' && $obj[2] != 'address' && $obj[2] != 'promotions' && $obj[2] != 'products' && $obj[2] != 'products') {
//                $permission = array_slice($obj, 1, -1);
//                $validPermissionsName = implode(".", $permission);
//                $root = $obj[1];

//                $validPermissions[] = $validPermissionsName;
//            }
//        }

        $validPermissions = array_unique($validPermissions);
        $defaultRoute = '';
        if (count($rolePermissions) > 0) {
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
            'filledMainPermissions'=>   $filledMainPermissions,
            'filledSubPermissions'=> $filledSubPermissions ,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

}
