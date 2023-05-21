<?php

namespace Webkul\Admin\Repositories\User;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;

class UserRepository extends Repository {

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model() {
        return 'Webkul\User\Contracts\Admin';
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

        // Search by Status
        if ($request->exists('status') && ($request['status'] != null)) {
            $query->where('status', $request['status']);
        }

        // Search by Area
        if ($request->exists('area_id') && !empty($request['area_id'])) {
            $query->whereHas('areas', function ($query) use ($request) {
                $query->where('areas.id',  $request['area_id']);
                //$query->whereIn('areas.id',  $request['area_id']);
            });
        }

        // Search by Area
        if ($request->exists('role_id') && !empty($request['role_id'])) {
            $query->whereHas('roles', function ($query) use ($request) {
                $query->where('roles.id',  $request['role_id']);
                //$query->whereIn('roles.id',  $request['role_id']);
            });
        }

        if ($request->exists('filter') && !empty($request['filter'])) {
            $value = "%{$request->filter}%";
            //$query->where('status', $request['status']);
            $query->where('name', 'like', $value);
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
        $data['password'] = bcrypt($data['password']);
        $data['status'] = isset($data['status']) ? $data['status'] : 1;
        $user = $this->model->create($data);

        if ($data['areas']) {
            $user->areas()->sync($data['areas']);
        }

        if ($data['roles']) {
            $user->assignRole($data['roles']);
        }

        // Store image
        $this->saveImgBase64WithoutWebP($data, $user);

        return $user;
    }

    /**
     * @param  array  $data
     * @param  int  $id
     * @param  string  $attribute
     * @return User
     */
    public function update(array $data, $id, $attribute = "id") {

        $user = $this->findOrFail($id);
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if ($data['areas']) {
            $user->areas()->sync($data['areas']);
        }

        if ($data['roles']) {
            $user->assignRole($data['roles']);
        }

        // Store image
        if (isset($data['image'])) {
            $this->saveImgBase64WithoutWebP($data, $user);
        }
        return $user;
    }

    public function setStatus($userModle, $data) {
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