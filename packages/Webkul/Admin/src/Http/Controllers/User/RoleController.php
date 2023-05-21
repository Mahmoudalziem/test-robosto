<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\Request;
use Webkul\Core\Http\Controllers\BackendBaseController;
 
use Webkul\Admin\Repositories\Role\RoleRepository;
 

class RoleController extends BackendBaseController {

    protected $roleRepository;

    public function __construct(RoleRepository $roleRepository) {
        $this->roleRepository = $roleRepository;
    }

    public function fetchRoles() {

        $roles = $this->roleRepository->all(['id', 'slug']);
 
        return $this->responseSuccess($roles);
    }

}
