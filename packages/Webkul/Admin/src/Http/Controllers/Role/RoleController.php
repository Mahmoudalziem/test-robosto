<?php

namespace Webkul\Admin\Http\Controllers\Role;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Repositories\Role\RoleRepository;
use Webkul\Admin\Http\Requests\Role\RoleRequest;
use Webkul\Admin\Http\Resources\Role\RoleAll;
use Webkul\Admin\Http\Resources\Role\RoleSingle;
use Webkul\User\Models\Role;
use Webkul\User\Models\PermissionCategory;
use Webkul\Admin\Http\Resources\Permission\PermissionCategoryDataAll;
use function DeepCopy\deep_copy;

class RoleController extends BackendBaseController {

    protected $roleRepository;

    public function __construct(RoleRepository $roleRepository) {
        $this->roleRepository = $roleRepository;
    }

    public function index(Request $request) {

        $roles = $this->roleRepository->list($request);
        $data = new RoleAll($roles);
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function show(Role $role) {
        $role = new RoleSingle($role);
        return $this->responseSuccess($role);
    }

    public function create(RoleRequest $request) {

        $role = $this->roleRepository->create($request->all());
 
        Event::dispatch('admin.role.created', $role);
        Event::dispatch('admin.log.activity', ['create', 'role', $role, auth('admin')->user(), $role]);

       return $this->responseSuccess(new RoleSingle($role), 'role-created!');
    }

    public function update($id, RoleRequest $request) {

        $role = $this->roleRepository->with('translations')->findOrFail($id);
        $before = deep_copy($role);

        $role = $this->roleRepository->update($request->all(), $role);

        Event::dispatch('admin.log.activity', ['update', 'role', $role, auth('admin')->user(), $role, $before]);

        return $this->responseSuccess(null, "role-updated");
    }

    public function updateStatus(Role $role, Request $request) {
        $this->validate($request, [
            'status' => 'required|numeric|in:0,1',
        ]);

        $before = deep_copy($role);

        $role = $this->roleRepository->setStatus($role, $request->only('status'));

        Event::dispatch('admin.role.set-status', $role);
        Event::dispatch('admin.log.activity', ['update-status', 'role', $role, auth('admin')->user(), $role, $before]);

        return $this->responseSuccess();
    }

    public function delete(Role $role) {

        $before = deep_copy($role);
        $role->delete();

        Event::dispatch('admin.log.activity', ['delete', 'role', $role, auth('admin')->user(), $before]);

        return $this->responseSuccess($role, 'role-deleted');
    }

    public function fetchRoles() {

        $roles = $this->roleRepository->all(['id', 'slug']);

        return $this->responseSuccess($roles);
    }

    public function fetchPermissionsData() {
        $permissionCategories =  PermissionCategory::with(['children','children.permissions','children.children.permissions','permissions']);
        $permissionCategories =$permissionCategories->where('parent_id',null)->get();
         $permissionCategories=new  PermissionCategoryDataAll($permissionCategories);
        return $this->responseSuccess($permissionCategories);
    }

}
