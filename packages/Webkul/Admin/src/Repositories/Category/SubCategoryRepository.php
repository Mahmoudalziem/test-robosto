<?php

namespace Webkul\Admin\Repositories\Category;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;

class SubCategoryRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Category\Contracts\SubCategory';
    }

    // subcategory search by name and select category
    public function list($request){
        $query = $this->positioned()->newQuery();


        // handle sort option
        if ($request->has('sort') && !empty($request->sort) ) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'asc');
        }

        // area  // source  // date1,date2
        if ($request->exists('category_id') && !empty($request['category_id'])) {

            $query->whereHas('parentCategories',  function(Builder $query) use ($request) {
                $query->where('category_id', $request['category_id']);
            });

        }

        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->whereHas('translations', function($query) use($request){
                $value = "%{$request->filter}%";
                $query->where('name', 'like', $value);
            });

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
     * @return \Webkul\Category\Contracts\SubCategory
     */
    public function create(array $data)
    {
        $subCategory = $this->model->create($data);

        if (isset($data['categories'])) {
            $subCategory->parentCategories()->sync($data['categories']);
        }

        // Store image
        $this->saveImgBase64($data, $subCategory, 'image', true);

        return $subCategory;
    }

    /**
     * @param  array  $data
     * @param  mixed  $subCategory
     * @param  string  $attribute
     * @return \Webkul\Category\Contracts\SubCategory
     */
    public function update(array $data, $subCategory, $attribute = "id")
    {
        $subCategory->update($data);

        if (isset($data['categories'])) {
            $subCategory->parentCategories()->sync($data['categories']);
        }

        // Store image
        if (isset($data['image'])) {
            $this->saveImgBase64($data, $subCategory, 'image', true);
        }

        return $subCategory;
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