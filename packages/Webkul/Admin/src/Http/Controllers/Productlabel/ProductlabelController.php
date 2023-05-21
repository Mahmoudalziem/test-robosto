<?php

namespace Webkul\Admin\Http\Controllers\Productlabel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Resources\Productlabel\ProductlabelAll;
use Webkul\Admin\Http\Resources\Productlabel\ProductlabelSingle;
use Webkul\Admin\Http\Requests\Productlabel\ProductlabelRequest;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Repositories\Productlabel\ProductlabelRepository;
use function DeepCopy\deep_copy;


class ProductlabelController extends BackendBaseController {

 
    protected $productlabelRepository;
 
    public function __construct(ProductlabelRepository $productlabelRepository) {
        $this->productlabelRepository = $productlabelRepository;
    }
 
    public function index(Request $request) {
        $products = $this->productlabelRepository->list($request);

        $data = new ProductlabelAll($products);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function create(ProductlabelRequest $request) {

        $productlabel = $this->productlabelRepository->create($request->all());

        Event::dispatch('productlabel.created', $productlabel);
        Event::dispatch('admin.log.activity', ['create', 'label', $productlabel, auth('admin')->user(), $productlabel]);

        return $this->responseSuccess(new ProductlabelSingle($productlabel));
    }

    public function show(int $id) {
        $productlabel = $this->productlabelRepository->findOrFail($id);
 
        Event::dispatch('productlabel.show', $productlabel);

        return $this->responseSuccess(new ProductlabelSingle($productlabel));
    }

    public function update(ProductlabelRequest $request, $id) {
        
        $productlabel = $this->productlabelRepository->with('translations')->findOrFail($id);
        
        $before = deep_copy($productlabel);
        
        $productlabel = $this->productlabelRepository->update($request->all(), $productlabel);
        
        Event::dispatch('productlabel.updated', $productlabel);
        Event::dispatch('admin.log.activity', ['update', 'productlabel', $productlabel, auth('admin')->user(), $productlabel, $before]);

        return $this->responseSuccess($productlabel);
    }
 

}
