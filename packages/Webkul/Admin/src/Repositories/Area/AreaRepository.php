<?php

namespace Webkul\Admin\Repositories\Area;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;

class AreaRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Area\Contracts\Area';
    }


    public function list($request){
        $query = $this->newQuery();

        // handle sort option
        if ($request->has('sort') && !empty($request->sort) ) {

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
     * @return \Webkul\Brand\Contracts\Brand
     */
    public function create(array $data)
    {
        $area = $this->model->create($data);
        return $area;
    }

    /**
     * @param  array  $data
     * @param  mixed  $area
     * @param  string  $attribute
     * @return \Webkul\Brand\Contracts\Brand
     */
    public function update(array $data, $area)
    {
        $area->update($data);

        return $area;
    }

    /**
     * @param  int  $id
     * @return void
     */
    public function delete($id)
    {
        $model=$this->find($id);
        parent::delete($id);

    }

}