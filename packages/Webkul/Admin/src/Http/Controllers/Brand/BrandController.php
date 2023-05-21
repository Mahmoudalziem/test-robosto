<?php

namespace Webkul\Admin\Http\Controllers\Brand;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Requests\Brand\BrandRequest;
use Webkul\Admin\Http\Resources\Brand\BrandAll;
use Webkul\Admin\Http\Resources\Brand\BrandProducts;
use Webkul\Admin\Repositories\Brand\BrandRepository;
use Webkul\Admin\Repositories\Product\ProductRepository;
use Webkul\Brand\Models\Brand;
use Webkul\Core\Http\Controllers\BackendBaseController;
use function DeepCopy\deep_copy;

class BrandController extends BackendBaseController
{

    /**
     * BrandRepository object
     *
     * @var \Webkul\Admin\Repositories\Brand\BrandRepository
     */
    protected $brandRepository;


    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Admin\Repositories\Brand\BrandRepository  $brandRepository
     * @return void
     */
    public function __construct(BrandRepository $brandRepository) {
        $this->brandRepository = $brandRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request  $request)
    {
        $brands = $this->brandRepository->list($request);
        $data=new BrandAll($brands);
        return $this->responsePaginatedSuccess($data ,null, $request);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllBrands()
    {
        $brands = $this->brandRepository->all();

        return $this->responseSuccess($brands);
    }

    public function productsByBrandId(Brand $brand,ProductRepository $productRepository, Request $request)
    {
        $request['brand_id']=$brand->id;
        $products = $productRepository->listProductsByBrandID($request );
        $data=new BrandProducts($products);
        return $this->responsePaginatedSuccess($data,null,$request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(BrandRequest $request)
    {

        $brand = $this->brandRepository->create($request->all());

        Event::dispatch('brand.created', $brand);
        Event::dispatch('admin.log.activity', ['create', 'brand', $brand, auth('admin')->user(), $brand]);

        return $this->responseSuccess($brand);
    }

    /**
     * Show the specified brand.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $brand = $this->brandRepository->findOrFail($id);

        Event::dispatch('brand.show', $brand);

        return $this->responseSuccess($brand);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(BrandRequest $request, $id)
    {
        $brand = $this->brandRepository->with('translations')->findOrFail($id);

        $before = deep_copy($brand);

        $brand = $this->brandRepository->update($request->all(), $brand);

        Event::dispatch('brand.updated', $brand);
        Event::dispatch('admin.log.activity', ['update', 'brand', $brand, auth('admin')->user(), $brand, $before]);

        return $this->responseSuccess($brand);

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

        $brand = $this->brandRepository->with('translations')->findOrFail($id);
        $before = deep_copy($brand);

        $brand = $this->brandRepository->update($request->only('status'), $brand);

        Event::dispatch('brand.updated-status', $brand);
        Event::dispatch('admin.log.activity', ['update-status', 'brand', $brand, auth('admin')->user(), $brand, $before]);

        return $this->responseSuccess($brand);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $brand = $this->brandRepository->with('translations')->findOrFail($id);

        $this->brandRepository->delete($id);

        Event::dispatch('admin.log.activity', ['delete', 'brand', $brand, auth('admin')->user(), $brand]);

        return $this->responseSuccess(null);
    }

}
