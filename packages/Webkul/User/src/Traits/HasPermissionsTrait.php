<?php

namespace Webkul\User\Traits;

use Webkul\User\Models\PermissionProxy;
use Webkul\User\Models\RoleProxy;

trait HasPermissionsTrait
{

    // can assdign permissions to admin
    public function givePermissionsTo(...$permissions)
    {

        $permissions = $this->getAllPermissions($permissions);

        if ($permissions === null) {
            return $this;
        }
        $this->permissions()->saveMany($permissions);
        return $this;
    }

    // can remove permissions to admin
    public function withdrawPermissionsTo(...$permissions)
    {

        $permissions = $this->getAllPermissions($permissions);
        $this->permissions()->detach($permissions);
        return $this;
    }

    public function refreshPermissions(...$permissions)
    {

        $this->permissions()->detach();
        return $this->givePermissionsTo($permissions);
    }

    public function hasPermissionTo($permission)
    {

        return $this->hasPermissionThroughRole($permission) || $this->hasPermission($permission);
    }

    public function hasPermissionThroughRole($permission)
    {

        foreach ($permission->roles as $role) {
            if ($this->roles->contains($role)) {
                return true;
            }
        }
        return false;
    }

    public function hasRole($roles)
    {
        $adminRoles = $this->roles->pluck('slug')->toArray();

        foreach ($roles as $role) {
            if (in_array($role, $adminRoles)) {
                return true;
            }
        }
        return false;
    }

    public function assignRole($roles)
    {
        return $this->roles()->sync($roles);
    }

    public function roles()
    {

        return $this->belongsToMany(RoleProxy::modelClass(), 'admin_roles');
    }

    public function getRoleNames()
    {
        return $this->roles->pluck('slug');
    }

    // permissions through roles
    public function rolePermissions()
    {

        $rolesWithPermissions = $this->roles()->with('permissions')->get();
        $permissionsData = [];
        foreach ($rolesWithPermissions as $role) {
            $permissions = $role->permissions()->orderBy('id')->get();
            if (count($permissions) > 0) {
                foreach ($permissions as $permission) {
                    if (!in_array($permission->route_name, $permissionsData)) {
                        $permissionsData[] = $permission->route_name;
                    }
                }
            }
        }
        return $permissionsData;
    }

    // check if this permission is exist in role permissions
    public function rolePermissionExists($permission)
    {
        if (in_array($permission, $this->rolePermissions())) {
            return true;
        }
        return false;
    }

    // permissions through roles
    public function roleMainPermissions()
    {

        $rolesWithPermissions = $this->roles()->with('permissions')->get();
        $permissionsData = collect();
        foreach ($rolesWithPermissions as $role) {
            $permissions = $role->permissions()->with(['category', 'category.parent'])->orderBy('id')->get();
            if (count($permissions) > 0) {
                foreach ($permissions as $permission) {
                    $permissionsData->push($permission);
                }
            }
        }
        return $permissionsData;
    }

    public function getRolePermissionNames()
    {
        $rolesWithPermissions = $this->roles()->with('permissions')->get();
        $permissionsData = [];
        foreach ($rolesWithPermissions as $role) {
            $permissions = $role->permissions()->orderBy('id')->get();
            if (count($permissions) > 0) {
                foreach ($permissions as $permission) {
                    if (!in_array($permission->name, $permissionsData)) {
                        $permissionsData[] = $permission->name;
                    }
                }
            }
        }

        return $permissionsData;
    }

    public function permissions()
    {

        return $this->belongsToMany(PermissionProxy::modelClass(), 'admin_permissions');
    }

    protected function hasPermission($permission)
    {

        return (bool) $this->permissions->where('slug', $permission->slug)->count();
    }

    protected function getAllPermissions(array $permissions)
    {

        return Permission::whereIn('route_name', $permissions)->get();
    }
}