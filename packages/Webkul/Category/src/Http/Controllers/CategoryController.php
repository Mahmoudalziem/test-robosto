<?php

namespace Webkul\Category\Http\Controllers;

use Illuminate\Support\Facades\Event;
use Webkul\Category\Http\Resources\CategoryAll;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Http\Resources\PopularCategories;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Category\Http\Resources\Category as CategoryResource;

class CategoryController extends BackendBaseController
{


    /**
     * CategoryRepository object
     *
     * @var \Webkul\Category\Repositories\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Category\Repositories\CategoryRepository  $categoryRepository
     * @return void
     */
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = $this->categoryRepository->with(['subCategories'  =>  function($q) {
            $q->positioned()->active();
        }])->active()->positioned()->get();

        $data = new CategoryAll($categories);

        return $this->responseSuccess($data);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function popular()
    {
        $categories = $this->categoryRepository->active()->positioned()->limit(6)->get();

        $data = new PopularCategories($categories);

        return $this->responseSuccess($data);
    }


    /**
     * Show the specified category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = $this->categoryRepository->with(['subCategories'  =>  function($q) {
            $q->active();
        }])->findOrFail($id);

        $data = new CategoryResource($category);

        return $this->responseSuccess($data);
    }

}