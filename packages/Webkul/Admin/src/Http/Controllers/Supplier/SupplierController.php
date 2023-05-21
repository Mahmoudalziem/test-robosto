<?php

namespace Webkul\Admin\Http\Controllers\Supplier;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Purchase\Models\PurchaseOrder;
use Webkul\Admin\Http\Resources\Supplier\SupplierAll;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Requests\Supplier\SupplierRequest;
use Webkul\Admin\Http\Resources\Supplier\SupplierProducts;
use Webkul\Admin\Repositories\Supplier\SupplierRepository;
use Webkul\Admin\Http\Resources\Supplier\SupplierProductsSkus;
use Webkul\Product\Models\Product;
use Webkul\Purchase\Models\PurchaseOrderProduct;
use function DeepCopy\deep_copy;

class SupplierController extends BackendBaseController
{
    /**
     * SupplierRepository object
     *
     * @var SupplierRepository
     */
    protected $supplierRepository;


    /**
     * Create a new controller instance.
     *
     * @param SupplierRepository $supplierRepository
     * @return void
     */
    public function __construct(SupplierRepository $supplierRepository)
    {
        $this->supplierRepository = $supplierRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $suppliers = $this->supplierRepository->list($request);

        $data = new SupplierAll($suppliers);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param SupplierRequest $request
     * @return View
     */
    public function create(SupplierRequest $request)
    {
        $supplier = $this->supplierRepository->create($request->all());

        Event::dispatch('supplier.created', $supplier);
        Event::dispatch('admin.log.activity', ['create', 'supplier', $supplier, auth('admin')->user(), $supplier]);

        return $this->responseSuccess($supplier);
    }

    /**
     * Show the specified supplier.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $supplier['data'] = $this->supplierRepository->with(['areas'])->findOrFail($id);

        Event::dispatch('supplier.show', $supplier['data']);

        return $this->responseSuccess($supplier);
    }


    /**
     * Show the specified supplier.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function getProducts(Request $request, $id)
    {
        $productsSearched = [];
        if ($request->has('text') && !empty($request->text)) {
            $productsSearched = Product::search(trim($request->text))->get()->pluck('id')->toArray();
        }

        $supplier = $this->supplierRepository->findOrFail($id);

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;

        $pagination = $supplier->supplierProducts();
        if (count($productsSearched)) {
            $pagination = $pagination->whereIn('product_id', $productsSearched);
        }
        $pagination = $pagination->paginate($perPage);

        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        $data = new SupplierProducts($pagination);

        Event::dispatch('supplier.get-products', $supplier);

        return $this->responsePaginatedSuccess($data, null, $request);
    }


    /**
     * Show the specified supplier.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function getProductSkus(Request $request)
    {

        $purchaseOrder = PurchaseOrderProduct::where('product_id', $request->product_id)->whereHas('purchaseOrder', function ($q) use ($request) {
            $q->where('supplier_id', $request->supplier_id);
        });

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        // $pagination = PurchaseOrderProduct::whereIn('purchase_order_id', $purchaseOrder)->paginate($perPage);
        $pagination = $purchaseOrder->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        $data = new SupplierProductsSkus($pagination);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(SupplierRequest $request, $id)
    {
        $supplier = $this->supplierRepository->findOrFail($id);
        $before = deep_copy($supplier);

        $supplier = $this->supplierRepository->update($request->all(), $supplier);

        Event::dispatch('admin.log.activity', ['update', 'supplier', $supplier, auth('admin')->user(), $supplier, $before]);

        Event::dispatch('supplier.updated', $supplier);

        return $this->responseSuccess($supplier);
    }

    public function productDelete($supplier_id, $product_id)
    {
        $supplier = $this->supplierRepository->findOrFail($supplier_id);
        $product = Product::findOrFail($product_id);

        $supplier->products()->detach($product_id);

        Event::dispatch('admin.log.activity', ['product-delete', 'supplier', $supplier, auth('admin')->user(), $product]);

        return $this->responseSuccess(null);
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

        $supplier = $this->supplierRepository->findOrFail($id);
        $before = deep_copy($supplier);

        $supplier = $this->supplierRepository->update($request->only('status'), $supplier);

        Event::dispatch('supplier.updated-status', $supplier);
        Event::dispatch('admin.log.activity', ['update-status', 'supplier', $supplier, auth('admin')->user(), $supplier, $before]);

        return $this->responseSuccess($supplier);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $supplier = $this->supplierRepository->findOrFail($id);

        $this->supplierRepository->delete($id);

        Event::dispatch('admin.log.activity', ['delete', 'supplier', $supplier, auth('admin')->user(), $supplier]);

        return $this->responseSuccess(null);
    }
}
