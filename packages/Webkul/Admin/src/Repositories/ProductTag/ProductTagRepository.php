<?php

namespace Webkul\Admin\Repositories\ProductTag;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;


class ProductTagRepository extends Repository {

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model() {
        return 'Webkul\Product\Contracts\ProductTag';
    }

    /**
     * @param $request
     * @return \Webkul\Product\Contracts\Product
     */
    public function list($request) {

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


        // if filter
        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->whereTranslationLike('name', '%' . $request->filter . '%');
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
     * @return \Webkul\Product\Contracts\Product
     */
    public function create(array $data) {

        $data['status']=1;
        $productTag = $this->model->create($data);

        return $productTag;
    }

    /**
     * @param  array  $data
     * @param  mixed  $productTag
     * @param  string  $attribute
     * @return \Webkul\Product\Contracts\Product
     */
    public function update(array $data, $productTag, $attribute = "id") {
        $productTag->update($data);

        return $productTag;
    }


    /**
     * @param  int  $id
     * @return void
     */
    public function delete($id) {
        parent::delete($id);
    }


}
