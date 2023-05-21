<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\Request;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\User\Models\Admin;
use Webkul\Admin\Repositories\User\UserRepository;
use Webkul\Admin\Http\Requests\User\UserRequest;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Resources\User\AdminSingle;
use Webkul\Admin\Http\Resources\User\AdminAll;
class UserController extends BackendBaseController {

    protected $userRepository;

    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
    }

    public function list(Request $request) {

        $admins = $this->userRepository->list($request);
        $data = new AdminAll($admins);
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function show(Admin $user) {
        $user = new AdminSingle($user);
        return $this->responseSuccess($user);
    }

    public function add(UserRequest $request) {
        $user = $this->userRepository->create($request->all());
        
        Event::dispatch('admin.user.created', $user);
        Event::dispatch('admin.log.activity', ['create', 'user', $user, auth('admin')->user(), $user]);

        return $this->responseSuccess(new AdminSingle($user), 'New User has been created!');
    }

    public function update(Admin $user, UserRequest $request) {
 
        $data = $request->all();
        $before = clone $user;

        $this->userRepository->update($data, $user->id);

        Event::dispatch('admin.log.activity', ['update', 'user', $user, auth('admin')->user(), $user, $before]);

        return $this->responseSuccess(null, "Admin has been updated!");
    }

    public function setStatus(Admin $user, Request $request) {
        $this->validate($request, [
            'status' => 'required|numeric|in:0,1',
        ]);
        $before = clone $user;
        $user = $this->userRepository->setStatus( $user,$request->only('status'));
        
        Event::dispatch('admin.user.set-status', $user);
        Event::dispatch('admin.log.activity', ['update-status', 'user', $user, auth('admin')->user(), $user, $before]);
        
        return $this->responseSuccess();
    }
}
