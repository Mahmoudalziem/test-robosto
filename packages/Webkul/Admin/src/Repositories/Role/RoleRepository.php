<?php

namespace Webkul\Admin\Repositories\Role;

use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;
use \Webkul\User\Models\Permission;
use Illuminate\Support\Str;
class RoleRepository extends Repository {

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model() {
        return \Webkul\User\Models\Role::class;
    }

    public function list($request) {
        $query = $this->newQuery();

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }
        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

    /**
     * @param  array  $data
     * @return User
     */
    public function create(array $data) {

        $data['guard_name'] = 'admin';
        if (isset($data['admin_permissions'])) {
            $adminPermissions = $data['admin_permissions'];
            unset($data['admin_permissions']);
        }
        
        $data['slug']=Str::slug($data['en']['name']);
        $role = $this->model->create($data);


        if ($data['permissions']) {
            $role->assignPermission($data['permissions']);
        }

        if (isset($adminPermissions)) {
            $permissioins = Permission::all();
            $role->assignPermission($permissioins);
        }
        return $role;
    }

    /**
     * @param  array  $data
     * @param  mixed  $role
     * @param  string  $attribute
     * @return User
     */
    public function update(array $data, $role, $attribute = "id") {

        if (isset($data['admin_permissions'])) {
            $adminPermissions = $data['admin_permissions'];
            unset($data['admin_permissions']);
        }

        $role->update($data);

        if (!isset($adminPermissions) && $data['permissions']) {
            $role->assignPermission($data['permissions']);
        }
        if (isset($adminPermissions)) {
            $permissioins = Permission::all();
            $role->assignPermission($permissioins);
        }
        return $role;
    }

    public function updateStatus($userModle, $data) {
        // Get the token
        $userModle->update($data);
        return $userModle;
    }

    /**
     * @param  int  $id
     * @return void
     */
    public function delete($id) {
        parent::delete($id);
    }

}
