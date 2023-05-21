<?php

namespace Webkul\Admin\Repositories\Category;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Webkul\Category\Contracts\Category;
use Webkul\Core\Eloquent\Repository;

class CategoryRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Category\Contracts\Category';
    }

    /**
     * @param $request
     * @return \Webkul\Core\Contracts\Shelve
     */
    public function list($request)
    {

        $query = $this->newQuery();

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
     * @return Category
     */
    public function create(array $data)
    {

        $category = $this->model->create($data);

        // Store Sub Categories
        if (isset($data['sub_categories'])) {
            $category->subCategories()->sync($data['sub_categories']);
        }

        // Store image
        $this->saveImgBase64($data, $category, 'image', true);

        return $category;
    }

    /**
     * @param  array  $data
     * @param  Model  $category
     * @param  string  $attribute
     * @return Category
     */
    public function update(array $data, $category, $attribute = "id")
    {
        $category->update($data);

        // Store image
        if (isset($data['image'])) {
            $this->saveImgBase64($data, $category, 'image', true);
        }

        // Store Sub Categories
        if (isset($data['sub_categories'])) {
            $category->subCategories()->sync($data['sub_categories']);
        }

        return $category;
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