<?php

namespace Webkul\Admin\Repositories\Alert;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Core\Eloquent\Repository;
use Webkul\User\Models\Admin;

class AlertRepository extends Repository {

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model() {
        return 'Webkul\Core\Contracts\Alert';
    }

    public function me($admin, $request) {

        $adminRoles = $admin->roles->pluck('slug')->toArray();
        $query = $this->newQuery();
        //  $query = new Admin();
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

        //  $query=$query->find($admin->id);


        $query->whereHas('admins', function($q) use($admin) {
            $q->where('admin_id', $admin->id);
        });


        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->with('me')->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

    public function unreadCount($admin) {

        $query = $this->newQuery();
        $query->whereHas('admins', function($q) use($admin) {
            $q->where('admin_id', $admin->id);
            $q->where('read', 0);
        });

        return $query->count();
    }

    public function read($admin, $request) {
        $alert = $this->findOrFail($request['alert_id']);
        return $alert->admins()->sync([$admin->id => ['read' => 1]]);
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

}
