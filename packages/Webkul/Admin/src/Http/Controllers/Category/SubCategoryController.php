<?php

namespace Webkul\Admin\Http\Controllers\Category;

use Illuminate\Http\Request;
use App\Jobs\TriggerFCMRTDBJob;
use function DeepCopy\deep_copy;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Resources\Category\SubCategoryAll;
use Webkul\Admin\Http\Requests\Category\SubCategoryRequest;
use Webkul\Admin\Repositories\Category\SubCategoryRepository;
use Webkul\Admin\Http\Resources\Category\SubCategorySingle as SubCategoryResource;

class SubCategoryController extends BackendBaseController
{

    /**
     * SubCategoryRepository object
     *
     * @var \Webkul\Admin\Repositories\Category\SubCategoryRepository
     */
    protected $subCategoryRepository;


    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Admin\Repositories\Category\SubCategoryRepository  $subCategoryRepository
     * @return void
     */
    public function __construct(SubCategoryRepository $subCategoryRepository) {

        $this->subCategoryRepository = $subCategoryRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $subCategories = $this->subCategoryRepository->list($request);

        $data = new SubCategoryAll($subCategories); // using customer repository
        return $this->responsePaginatedSuccess($data ,null, $request);
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(SubCategoryRequest $request)
    {
        $subCategory = $this->subCategoryRepository->create($request->all());

        Event::dispatch('SubCategory.created', $subCategory);

        Event::dispatch('admin.log.activity', ['create', 'subCategory', $subCategory, auth('admin')->user(), $subCategory]);

        return $this->responseSuccess($subCategory);
    }

    /**
     * Show the specified SubCategory.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $subCategory = $this->subCategoryRepository->findOrFail($id);

        Event::dispatch('SubCategory.show', $subCategory);

        return $this->responseSuccess(new SubCategoryResource($subCategory));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(SubCategoryRequest $request, $id)
    {
        $subCategory = $this->subCategoryRepository->with('translations')->findOrFail($id);
        $before = deep_copy($subCategory);
        
        $subCategory = $this->subCategoryRepository->update($request->all(), $subCategory);

        TriggerFCMRTDBJob::dispatch('updated', 'subCategories');

        Event::dispatch('SubCategory.updated', $subCategory);
        Event::dispatch('admin.log.activity', ['update', 'subCategory', $subCategory, auth('admin')->user(), $subCategory, $before]);

        return $this->responseSuccess($subCategory);

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
        
        $subCategory = $this->subCategoryRepository->with('translations')->findOrFail($id);
        $before = deep_copy($subCategory);

        $subCategory = $this->subCategoryRepository->update($request->only('status'), $subCategory);

        TriggerFCMRTDBJob::dispatch('updated', 'subCategories');

        Event::dispatch('subCategory.updated-status', $subCategory);
        Event::dispatch('admin.log.activity', ['update-status', 'subCategory', $subCategory, auth('admin')->user(), $subCategory, $before]);

        return $this->responseSuccess($subCategory);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $subCategory = $this->subCategoryRepository->with('translations')->findOrFail($id);

        $this->subCategoryRepository->delete($id);

        TriggerFCMRTDBJob::dispatch('updated', 'subCategories');

        Event::dispatch('admin.log.activity', ['delete', 'subCategory', $subCategory, auth('admin')->user(), $subCategory]);

        return $this->responseSuccess(null);
    }

}