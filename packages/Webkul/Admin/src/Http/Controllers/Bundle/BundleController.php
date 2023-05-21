<?php

namespace Webkul\Admin\Http\Controllers\Bundle;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Resources\Bundle\BundleAll;
use Webkul\Admin\Http\Requests\Bundle\BundleRequest;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Repositories\Bundle\BundleRepository;
use Webkul\Admin\Http\Resources\Bundle\Bundle as BundleResource;
use Webkul\Admin\Repositories\Product\ProductRepository;
use Webkul\Admin\Http\Resources\Bundle\BundleProductsSearch;
use function DeepCopy\deep_copy;
use Webkul\Area\Models\Area;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\Order;

class BundleController extends BackendBaseController {

    /**
     * BundleRepository object
     *
     * @var \Webkul\Admin\Repositories\Bundle\BundleRepository
     */
    protected $bundleRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Admin\Repositories\Bundle\BundleRepository  $bundleRepository
     * @return void
     */
    public function __construct(BundleRepository $bundleRepository) {
        $this->bundleRepository = $bundleRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request) {
        $bundles = $this->bundleRepository->list($request);

        $data = new BundleAll($bundles);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(BundleRequest $request) {
        $items = $request->items;
        usort($items, fn($a, $b) => strcmp($a['id'], $b['id'])); // sort item by id
        $request['items'] = $items;

        $check = $this->checkItemsAvailableInAreaAndWarehouse($request->all());
        if ($check) {
            return $check;
        }

        $bundle = $this->bundleRepository->create($request->all());

        Event::dispatch('bundle.created', $bundle);
        Event::dispatch('admin.log.activity', ['create', 'bundle', $bundle, auth('admin')->user(), $bundle]);

        return $this->responseSuccess($bundle);
    }

    private function checkItemsAvailableInAreaAndWarehouse(array $data) {
        // check if items available in area and warehouse
        $areas = Area::whereIn('id', $data['areas'])->active()->get();
        foreach ($areas as $area) {
            foreach ($data['items'] as $item) {
                $InventoryArea = InventoryArea::where('area_id', $area->id)
                        ->where('product_id', $item['id'])
                        ->where('total_qty', '>', 0)
                        ->first();

                if ($InventoryArea && $InventoryArea['total_qty'] < $item['qty']) {

                    return $this->responseError(422, 'The qty of the item ( ' . $InventoryArea->product->name . ' ) is not enough in area ' . $area['name'] . ' to make bundle');
                }
 
                foreach ($area->warehouses()->active()->get()  as $warehouse) {
                    
                    $InventoryWarehouse = InventoryWarehouse::where('area_id', $area->id)
                                    ->where('warehouse_id', $warehouse->id)
                                    ->where('qty', '>', 0)
                                    ->where('product_id', $item['id'])->first();

                    if ($InventoryWarehouse && $InventoryWarehouse['qty'] < $item['qty']) {
                        return $this->responseError(422, 'item qty is not enough in warehouse ' . $warehouse['name'] . ' to make bundle');
                    }
                }
            }
        }
    }

    /**
     * Show the specified bundle.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id) {
        $bundle = $this->bundleRepository->with(['area', 'items'])->findOrFail($id);

        Event::dispatch('bundle.show', $bundle);

        return $this->responseSuccess(new BundleResource($bundle));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(BundleRequest $request, $id) {
        $items = $request->items;
        usort($items, fn($a, $b) => strcmp($a['id'], $b['id'])); // sort item by id
        $request['items'] = $items;

        $bundle = $this->bundleRepository->with('translations')->findOrFail($id);

        // Check that this bundle belongs an order
        $orderItem = OrderItem::whereHas('order', function ($query) {
                    $query->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_CANCELLED_FOR_ITEMS, Order::STATUS_DELIVERED]);
                })->where('bundle_id', $bundle->id)->first();
        if ($orderItem) {
            return $this->responseError(422, 'can_not_update_bundle_that_already_used_in_order');
        }


        $before = deep_copy($bundle);

        $bundle = $this->bundleRepository->update($request->all(), $bundle);

        Event::dispatch('admin.log.activity', ['update', 'bundle', $bundle, auth('admin')->user(), $bundle, $before]);

        Event::dispatch('bundle.updated', $bundle);

        return $this->responseSuccess($bundle);
    }

    /**
     * Update Status the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateStatus(Request $request, $id) {
        $this->validate($request, [
            'status' => 'required|numeric|in:0,1',
        ]);

        $bundle = $this->bundleRepository->with('translations')->findOrFail($id);
        $before = deep_copy($bundle);

        $bundle = $this->bundleRepository->updateStatus($request->only('status'), $bundle);

        Event::dispatch('bundle.updated-status', $bundle);
        Event::dispatch('admin.log.activity', ['update-status', 'bundle', $bundle, auth('admin')->user(), $bundle, $before]);

        return $this->responseSuccess($bundle);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function delete($id) {
        $bundle = $this->bundleRepository->with('translations')->findOrFail($id);
        
        // check if bundle has been used in order
        $bundleExistsInOrder = Order::whereHas('items', function ($query) use ($bundle) {
                    $query->where('bundle_id', $bundle->id);
                })->exists();
                
        if ($bundleExistsInOrder) {
            return $this->responseError(410, 'Bundle Already Exists In One Order Or More ! ');
        }

        $this->bundleRepository->delete($bundle);

        Event::dispatch('admin.log.activity', ['delete', 'bundle', $bundle, auth('admin')->user(), $bundle]);

        return $this->responseSuccess(null);
    }

    /**
     * Display a listing of the resource when Search.
     *
     * @param Request $request
     * @param ProductRepository $productRepository
     * @return JsonResponse
     */
    public function productsSearch(Request $request, ProductRepository $productRepository) {
        $products = $productRepository->whereTranslationLike('name', '%' . $request->q . '%')->orWhere('barcode', $request->q);

        $perPage = $request->has('per_page') ? (int) $request->per_page : 30;
        $pagination = $products->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        $data = new BundleProductsSearch($pagination);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

}
