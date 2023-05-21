<?php

namespace Webkul\Admin\Repositories\Shelve;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Core\Eloquent\Repository;

class ShelveRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Core\Contracts\Shelve';
    }

    /**
     * @param $request
     * @return \Webkul\Core\Contracts\Shelve
     */
    public function list($request)
    {

        $query = $this->newQuery();

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multi-sort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'asc');
        }


        // Search by Name
        if ($request->exists('name') && !empty($request['name'])) {
            $query->where('name', 'LIKE', '%' . $request['name'] . '%');
        }

        // Search by Position
        if ($request->exists('position') && !empty($request['position'])) {
            $query->where('position', 'LIKE', '%' . $request['position'] . '%');
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
     * @return \Webkul\Core\Contracts\Shelve
     */
    public function create(array $data)
    {
        $shelve = $this->model->create($data);

        return $shelve;
    }

    /**
     * @param  array  $data
     * @param  mixed  $shelve
     * @param  string  $attribute
     * @return \Webkul\Core\Contracts\Shelve
     */
    public function update(array $data, $shelve, $attribute = "id")
    {
        $shelve->update($data);

        return $shelve;
    }


    /**
     * @param  int  $id
     * @return void
     */
    public function delete($id)
    {
        parent::delete($id);
    }
}
