<?php

namespace Webkul\Admin\Http\Controllers\Category;

use App\Jobs\TriggerFCMRTDBJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Resources\Category\CategoriesAll;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Requests\Category\CategoryRequest;
use Webkul\Admin\Repositories\Category\CategoryRepository;
use Webkul\Admin\Repositories\Category\SubCategoryRepository;
use Webkul\Admin\Http\Resources\Category\CategorySingle as CategoryResource;
use function DeepCopy\deep_copy;

class CategoryController extends BackendBaseController
{
    /**
     * CategoryRepository object
     *
     * @var \Webkul\Admin\Repositories\Category\CategoryRepository
     */
    protected $categoryRepository;


    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Admin\Repositories\Category\CategoryRepository  $categoryRepository
     * @return void
     */
    public function __construct(CategoryRepository $categoryRepository) {

        $this->categoryRepository = $categoryRepository;
    }


    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * 
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $categories = $this->categoryRepository->list($request);

        $data = new CategoriesAll($categories);

        return $this->responsePaginatedSuccess($data, null, $request);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(CategoryRequest $request)
    {
        $category = $this->categoryRepository->create($request->all());

        Event::dispatch('admin.log.activity', ['create', 'category', $category, auth('admin')->user(), $category]);

        return $this->responseSuccess($category);
    }

    /**
     * Show the specified category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = $this->categoryRepository->with('subCategories')->findOrFail($id);

        Event::dispatch('category.show', $category);

        return $this->responseSuccess(new CategoryResource($category));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CategoryRequest $request, $id)
    {
        $category = $this->categoryRepository->with('translations')->findOrFail($id);
        $before = deep_copy($category);

        $category = $this->categoryRepository->update($request->all(), $category);

        Event::dispatch('admin.log.activity', ['update', 'category', $category, auth('admin')->user(), $category, $before]);

        TriggerFCMRTDBJob::dispatch('updated', 'categories');
        
        return $this->responseSuccess($category);
    }

    /**
     * Update Status the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        $this->validate($request, [
            'status'    =>  'required|numeric|in:0,1',
        ]);

        $category = $this->categoryRepository->with('translations')->findOrFail($id);
        $before = deep_copy($category);

        $category = $this->categoryRepository->update($request->only('status'), $category);

        TriggerFCMRTDBJob::dispatch('updated', 'categories');

        Event::dispatch('admin.log.activity', ['update-status', 'category', $category, auth('admin')->user(), $category, $before]);

        return $this->responseSuccess($category);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $category = $this->categoryRepository->with('translations')->findOrFail($id);

        $this->categoryRepository->delete($id);

        TriggerFCMRTDBJob::dispatch('updated', 'categories');

        Event::dispatch('admin.log.activity', ['delete', 'category', $category, auth('admin')->user(), $category]);

        return $this->responseSuccess(null);
    }

    public function listSubCategoyByCategory($id)
    {
        $category = $this->categoryRepository->findOrFail($id);
        
        return $this->responseSuccess($category->subCategories()->positioned()->get());

    }

    public function listProductsBySubcategory($id,SubCategoryRepository $subCategoryRepository)
    {
        $subCategory =$subCategoryRepository->findOrFail($id);
        return $this->responseSuccess($subCategory->products()->get());

    }
}