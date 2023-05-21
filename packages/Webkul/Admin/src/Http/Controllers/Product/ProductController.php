<?php

namespace Webkul\Admin\Http\Controllers\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Jobs\TriggerFCMRTDBJob;
use Webkul\Product\Models\Unit;
use function DeepCopy\deep_copy;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Webkul\Sales\Models\OrderItemSku;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Webkul\Admin\Http\Resources\Product\SKUCard;
use Webkul\Admin\Http\Resources\Product\UnitAll;
use Webkul\Purchase\Models\PurchaseOrderProduct;
use Webkul\Admin\Http\Resources\Product\ProductAll;

use Webkul\Admin\Http\Requests\Product\ProductRequest;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Inventory\Models\InventoryAdjustmentProduct;
use Webkul\Admin\Repositories\Product\ProductRepository;
use Webkul\Inventory\Models\InventoryTransactionProduct;
use Webkul\Admin\Http\Resources\Product\Product as ProductResource;
use Webkul\Admin\Http\Resources\Product\ProductSKUs;
use Webkul\Inventory\Models\InventoryProduct;

class ProductController extends BackendBaseController
{

    /**
     * ProductRepository object
     *
     * @var \Webkul\Admin\Repositories\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Admin\Repositories\Product\ProductRepository  $productRepository
     * @return void
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $products = $this->productRepository->list($request);

        $data = new ProductAll($products);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function listUnits()
    {
        $data = new UnitAll(Unit::all());
        return $this->responseSuccess($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(ProductRequest $request)
    {
        $product = $this->productRepository->create($request->all());

        Event::dispatch('product.created', $product);

        Event::dispatch('admin.log.activity', ['create', 'product', $product, auth('admin')->user(), $product]);

        return $this->responseSuccess($product);
    }

    /**
     * Show the specified product.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id)
    {
        $product = $this->productRepository->with(['unit', 'brand', 'subCategories'])->findOrFail($id);

        Event::dispatch('product.show', $product);

        return $this->responseSuccess(new ProductResource($product));
    }

    /**
     * Get all SKUs for the product.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getSku(Request $request, int $id)
    {
        $product = $this->productRepository->findOrFail($id);

        $productSKU = $this->productRepository->getProductSku($request->only(['warehouses']), $id);

        Event::dispatch('product.getSKUs', $product);

        return $this->responseSuccess(new ProductSKUs($productSKU));
    }

    /**
     * Get all SKUs for the product.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getSupplierBySku($sku)
    {

        $supplierBySKU = $this->productRepository->getSupplierBySku($sku);
        Event::dispatch('product.getSupplierBySKU', $sku);
        if ($supplierBySKU)
            return $this->responseSuccess($supplierBySKU);
        else
            return $this->responseError(422, "Supplier Not Found");
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(ProductRequest $request, $id)
    {
        $product = $this->productRepository->with('translations')->findOrFail($id);
        $before = deep_copy($product);

        $product = $this->productRepository->update($request->all(), $product);

        Event::dispatch('product.updated', $product);

        Event::dispatch('admin.log.activity', ['update', 'product', $product, auth('admin')->user(), $product, $before]);

        TriggerFCMRTDBJob::dispatch('product-updated', $product);

        return $this->responseSuccess($product);
    }

    /**
     * Update Status the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateStatus(Request $request, $id)
    {
        $this->validate($request, [
            'status' => 'required|numeric|in:0,1',
        ]);

        $product = $this->productRepository->with('translations')->findOrFail($id);
        $before = deep_copy($product);

        $product = $this->productRepository->update($request->only('status'), $product);

        Event::dispatch('admin.log.activity', ['update', 'product', $product, auth('admin')->user(), $product, $before]);

        TriggerFCMRTDBJob::dispatch('product-updated', $product);

        return $this->responseSuccess($product);
    }


    /**
     * Update Status the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateNote(Request $request, $id)
    {

        $this->validate($request, [
            'note' => 'required',
        ]);

        $product = $this->productRepository->with('translations')->findOrFail($id);
        $before = deep_copy($product);

        $product = $this->productRepository->update($request->only('note'), $product);

        Event::dispatch('product.updated-note', $product);
        Event::dispatch('admin.log.activity', ['update', 'product', $product, auth('admin')->user(), $product, $before]);

        return $this->responseSuccess($product);
    }

    /**
     * Collect all date related to the product
     *
     * @param Product $product
     *
     * @return mixed
     */
    public function skuCard(Request $request, $sku)
    {
        $perPage = $request->input('per_page', 15);
        $page    = $request->input('page', 1);
        $warehouse = $request->warehouse;
        $skuCount = $this->getSkuCount($sku, $warehouse);


        $query =  DB::select(
            DB::raw(
                "SELECT
                    P.id, P.sku, P.qty, P.created_at AS s_date, 'purchase-orders-profile' AS type, P.purchase_order_id AS type_id, P.warehouse_id AS 'to_warehouse', null AS 'from_warehouse'
                    FROM  purchase_order_products AS P WHERE P.sku = '{$sku}' {$this->handleWarehouseInQuery($warehouse, 'P')}
                UNION (SELECT
                    O.id, O.sku, O.qty, OD.created_at AS s_date, 'order-profile' AS type, OD.id AS type_id, null, OD.warehouse_id AS 'from_warehouse'
                    FROM order_item_skus O
                        INNER JOIN orders OD ON OD.id = O.order_id WHERE O.sku = '{$sku}' AND OD.status = 'delivered' {$this->handleWarehouseInQuery($warehouse, 'OD')}
                    )
                UNION (SELECT
                    T.id, T.sku, T.qty, T.created_at AS s_date, 'transfers-profile' AS type, TR.id AS type_id, TR.to_warehouse_id AS 'to_warehouse', TR.from_warehouse_id AS 'from_warehouse'
                    FROM inventory_transaction_products T
                        INNER JOIN inventory_transactions TR ON TR.id = T.inventory_transaction_id
                        WHERE T.sku = '{$sku}' {$this->handleWarehouseInTransactionQuery($warehouse, 'TR')}
                    )
                UNION (SELECT
                    J.id, J.sku, J.qty, J.created_at AS s_date, 'adjustments-profile' AS type, IJ.id AS type_id, null, IJ.warehouse_id AS 'from_warehouse'
                    FROM inventory_adjustment_products J
                        INNER JOIN inventory_adjustments IJ ON IJ.id = J.inventory_adjustment_id WHERE J.sku = '{$sku}' {$this->handleWarehouseInQuery($warehouse, 'IJ')}
                    )
                ORDER BY s_date ASC"
            )
        );

        // Handle Result Data Format
        $data = new SKUCard($query);
        $path = route('admin.app-management.sku.card', $sku);

        // Paginate the data
        $dataPaginated = $this->paginate($data, $perPage, $page, ['ahmed' => 'Taha'])->withPath($path);
        $custom = collect(['sku_stock_count' => $skuCount]);
        return $custom->merge($dataPaginated);
    }

    /**
     * @param mixed $warehouse
     * @param string $table
     *
     * @return string
     */
    private function handleWarehouseInQuery($warehouse, string $table)
    {
        return isset($warehouse) && !empty($warehouse) ?  "AND " . $table . ".warehouse_id = " . $warehouse : "";
    }

    /**
     * @param mixed $warehouse
     * @param string $table
     *
     * @return string
     */
    private function handleWarehouseInTransactionQuery($warehouse, string $table)
    {
        return isset($warehouse) && !empty($warehouse) ?  "AND ((" . $table . ".from_warehouse_id = " . $warehouse . ") OR (" . $table . ".to_warehouse_id = " . $warehouse . " ))" : "";
    }

    /**
     * @param string $sku
     * @param mixed $warehouse
     *
     * @return int
     */
    private function getSkuCount(string $sku, $warehouse)
    {
        $skuCount = InventoryProduct::where('sku', $sku);
        if (isset($warehouse) && !empty($warehouse)) {
            $skuCount = $skuCount->where('warehouse_id', $warehouse);
        }
        return $skuCount->count();
    }

    /**
     * @param mixed $items
     * @param int $perPage
     * @param null $page
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator(array_values($items->forPage($page, $perPage)->toArray()), $items->count(), $perPage, $page, $options);
    }
}
