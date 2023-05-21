<?php

namespace Webkul\Admin\Http\Controllers\ProductTag;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Resources\ProductTag\ProductTagAll;
use Webkul\Admin\Http\Resources\ProductTag\ProductTagSingle;
use Webkul\Admin\Http\Requests\ProductTag\ProductTagRequest;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Repositories\ProductTag\ProductTagRepository;
use function DeepCopy\deep_copy;


class ProductTagController extends BackendBaseController {

 
    protected $productTagRepository;
 
    public function __construct(ProductTagRepository $productTagRepository) {
        $this->productTagRepository = $productTagRepository;
    }
 
    public function index(Request $request) {
        $products = $this->productTagRepository->list($request);

        $data = new ProductTagAll($products);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function create(ProductTagRequest $request) {

        $productTag = $this->productTagRepository->create($request->all());

        Event::dispatch('productTag.created', $productTag);
        Event::dispatch('admin.log.activity', ['create', 'productTag', $productTag, auth('admin')->user(), $productTag]);

        return $this->responseSuccess(new ProductTagSingle($productTag));
    }

    public function show(int $id) {
        $productTag = $this->productTagRepository->findOrFail($id);
 
        Event::dispatch('productTag.show', $productTag);

        return $this->responseSuccess(new ProductTagSingle($productTag));
    }

    public function update(ProductTagRequest $request, $id) {

        $productTag = $this->productTagRepository->with('translations')->findOrFail($id);
        $before = deep_copy($productTag);

        $productTag = $this->productTagRepository->update($request->all(), $productTag);

        Event::dispatch('productTag.updated', $productTag);
        Event::dispatch('admin.log.activity', ['update', 'productTag', $productTag, auth('admin')->user(), $productTag, $before]);

        return $this->responseSuccess($productTag);
    }
 

}
