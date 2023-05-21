<?php

namespace Webkul\Admin\Repositories\Inventory;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Eloquent\Repository;
use Webkul\Inventory\Models\Warehouse;
use Illuminate\Container\Container as App;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Inventory\Models\InventoryAdjustment;
use Webkul\Inventory\Models\InventoryAdjustmentProduct;
use Webkul\Purchase\Models\PurchaseOrderProduct;
use Webkul\Bundle\Models\Bundle;
use Webkul\Bundle\Models\BundleItem;
use Webkul\User\Models\Admin;
use App\Exceptions\ResponseErrorException;
use Webkul\Core\Services\FixSKUs\FixSkus;
use Illuminate\Support\Carbon;

class InventoryAdjustmentRepository extends Repository
{

    protected $inventoryAdjustmentProductRepository;

    public function __construct(
        InventoryAdjustmentProductRepository $inventoryAdjustmentProductRepository,
        App $app
    ) {
        $this->inventoryAdjustmentProductRepository = $inventoryAdjustmentProductRepository;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return InventoryAdjustment::class;
    }

    public function list($request)
    {
        $query = $this->newQuery();
        $query = $query->byArea();
        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }

        // transaction type 0 inside /1 outside
        if ($request->exists('warehouse_id') && !empty($request['warehouse_id'])) {
            $query->where('warehouse_id', $request['warehouse_id']);
        }

        // status  1 canceled /2 pending /2 approved
        if ($request->exists('status') && is_numeric($request['status'])) {
            $query->where('status', $request['status']);
        }


        if ($request->exists('date_from') && !empty($request['date_from']) && $request->exists('date_to') && !empty($request['date_to'])) {
            $query->where(function ($q) use ($request) {
                $dateFrom = $request['date_from'] . ' 00:00:00';
                $dateTo = $request['date_to'] . ' 23:59:59';
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            });
        }

        // if filter by id
        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->where('id', 'LIKE', '%' . trim($request->filter) . '%');
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;

        $pagination = $query->with('warehouse')->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

    public function searchProduct($request)
    {
        // get prodcuts ids
        $productIds = Product::whereTranslationLike('name', "%" . $request['key'] . "%")->orWhere('barcode', $request['key'])->pluck('id')->toArray();

        return Product::whereHas('warehouses', function ($q) use ($productIds, $request) {
            $q->where('warehouse_id', $request['warehouse_id'])
                ->whereIn('product_id', $productIds);
        })->paginate(20);
    }

    public function selectProduct($request)
    {
        $productObj['product_id'] = $request['product']->id;
        $productObj['name'] = $request['product']->name;
        $productObj['image'] = $request['product']->image;

        // getProductSkuQty
        $fixSku = new FixSkus();
        $getProductSkuQty = collect($fixSku->getProductSku($request['warehouse_id'], $request['product']->id));
        $productObj['source_stock'] = $getProductSkuQty['warehouseQty']; // warehouseQty
        $productObj['source_stock_details'] = $getProductSkuQty['skuItems'];
        return $productObj;
    }

    public function showProductSku($sku, $request)
    {

        // initial values
        $area = Warehouse::find($request['warehouse_id'])->area_id;
        $details['purchasingQty'] = 0;
        $details['remainQty'] = 0;
        $details['soldQty'] = 0;
        $details['storeQty'] = 0;

        $purshaseProductOrders = PurchaseOrderProduct::where('sku', $sku)->first();
        if ($purshaseProductOrders) {
            $details['purchasingQty'] = $purshaseProductOrders->qty;
        }

        $inventoryProductSkus = InventoryProduct::where(['area_id' => $area, 'sku' => $request['sku']])->get();
        if ($inventoryProductSkus) {
            $details['remainQty'] = $inventoryProductSkus->sum('qty');
        }

        // sold (purchasingQty -remainQty )
        if ($details['remainQty'] && $details['purchasingQty']) {
            $details['soldQty'] = $details['purchasingQty'] - $details['remainQty'];
        }

        $inventoryWarehouseProductSkus = InventoryProduct::where(['area_id' => $area, 'warehouse_id' => $request['warehouse_id'], 'sku' => $request['sku']])->first();
        if ($inventoryWarehouseProductSkus) {
            $details['storeQty'] = $inventoryWarehouseProductSkus;
        }

        if (isset($request['inventory_adjustment_product_id'])) {

            $inventoryAdjustmentProduct = $this->inventoryAdjustmentProductRepository->findOneWhere(['id' => $request['inventory_adjustment_product_id'], 'sku' => $sku]);
            $details['image'] = $inventoryAdjustmentProduct->image ? $inventoryAdjustmentProduct->image_url : Product::find($inventoryWarehouseProductSkus->product_id)->image_url;
            $productStatus = $this->getAdjustmentStatus($inventoryAdjustmentProduct->status);
            $details['sku_status'] = $productStatus;
            $details['sku_note'] = $inventoryAdjustmentProduct->note;
            $details['store_qty_before'] = $inventoryAdjustmentProduct->qty_stock_before;
            $details['store_qty_after'] = $inventoryAdjustmentProduct->qty_stock_after;
        } else {
            $details['image'] = Product::find($inventoryWarehouseProductSkus->product_id)->image_url;
        }

        return $details;
    }

    public function deleteProduct($inventoryAdjustment, $productSku)
    {

        DB::beginTransaction();
        try {
            // remove item from inventory adjustment if status (pending)
            // Status => Over Qty  (Do Nothing) else increase stock
            if ($productSku->status != InventoryAdjustmentProduct::STATUS_OVERQTY) {

                // only return qty in area_from and warehouse_from
                $areaID = $inventoryAdjustment->area_id;
                $warehouseID = $inventoryAdjustment->warehouse_id;

                // Update Inventroy Balance
                // inventory Products (from_werehouse=source warehouse)
                // incrase the qty of product of selected sku row(id of inventory_products) in the selected warehouse (source store)
                $inventoryProducts = InventoryProduct::where(['area_id' => $areaID, 'warehouse_id' => $warehouseID, 'sku' => $productSku->sku])->first();
                if ($inventoryProducts) {
                    $inventoryProducts['qty'] = $inventoryProducts['qty'] + $productSku->qty;
                    $inventoryProducts->save();
                }

                // inventory Warehouses
                $inventoryWarehouse = InventoryWarehouse::where(['area_id' => $areaID, 'warehouse_id' => $warehouseID, 'product_id' => $productSku->product_id])->first();
                $inventoryWarehouse['qty'] = $inventoryWarehouse['qty'] + $productSku->qty;
                $inventoryWarehouse->save();

                // inventory Areas
                $inventoryArea = InventoryArea::where(['area_id' => $areaID, 'product_id' => $productSku->product_id])->first();
                $inventoryArea['total_qty'] = $inventoryArea['total_qty'] + $productSku->qty;
                $inventoryArea->save();
            }
            // remove item from transaction product
            $productSku->delete();

            // get needed data
            $inventoryAdjustmentProducts = $inventoryAdjustment->adjustmentProducts()->orderBy('id', 'desc')->get();
            // Handle Bundle Items
            $this->updateBundleQtyInAreaAndWarehouse($inventoryAdjustmentProducts, $inventoryAdjustment->area_id, $inventoryAdjustment->warehouse_id);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ResponseErrorException(422, 'Something went wrong while deleting sku !');
        }

        return $inventoryAdjustment;
    }

    public function create(array $request)
    {
        DB::beginTransaction();
        try {
            // master table data
            $area = Warehouse::find($request['warehouse_id'])->area_id;
            $adjustmentData = Arr::except($request, ['products']);
            $adjustmentData['status'] = InventoryAdjustment::STATUS_PENDING; // pending
            $adjustmentData['area_id'] = $area;

            // Details Table data
            $ProductsData = isset($request['products']) ? $request['products'] : null;

            $inventoryAdjustment = $this->model->create($adjustmentData);

            if ($ProductsData) {
                // Save Adjustment Products
                $this->saveAdjustmentProducts($request, $ProductsData, $inventoryAdjustment);

                // Reserve Quantities
                $this->reserveQuantities($inventoryAdjustment);
            }

            $this->createAction($inventoryAdjustment, $request['admin_id'], $adjustmentData['status'], $request['admin_type']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ResponseErrorException(422, 'Something went wrong while creating adjustment !');
        }

        return $inventoryAdjustment;
    }

    /**
     * @param mixed $request
     * @param mixed $ProductsData
     * @param mixed $inventoryAdjustment
     *
     * @return void
     */
    private function saveAdjustmentProducts($request, $ProductsData, $inventoryAdjustment)
    {
        $adjustmentProductsArr = array();
        foreach ($ProductsData as $row) {
            foreach ($row['skus'] as $sku) {
                if ((isset($sku['qty']) && (int) $sku['qty'] > 0) && (isset($sku['status']) && $sku['status'] != 0)) {

                    $adjustmentProductsData['product_id'] = $sku['product_id'];
                    $adjustmentProductsData['sku'] = $sku['sku'];
                    $inventoryWarehouseProductSkus = InventoryProduct::where(['warehouse_id' => $request['warehouse_id'], 'sku' => $sku['sku']])->first();
                    $adjustmentProductsData['qty'] = $sku['qty'];

                    $adjustmentProductsData['qty_stock_before'] = $inventoryWarehouseProductSkus ? $inventoryWarehouseProductSkus['qty'] : 0;
                    if ($sku['status'] == InventoryAdjustmentProduct::STATUS_LOST || $sku['status'] == InventoryAdjustmentProduct::STATUS_EXPIRED || $sku['status'] == InventoryAdjustmentProduct::STATUS_DAMAGED || $sku['status'] == InventoryAdjustmentProduct::STATUS_RETURN_TO_VENDOR) { //
                        $adjustmentProductsData['qty_stock_after'] = $inventoryWarehouseProductSkus ? $inventoryWarehouseProductSkus['qty'] - (int) $sku['qty'] : 0;
                    }
                    if ($sku['status'] == InventoryAdjustmentProduct::STATUS_OVERQTY) {

                        $adjustmentProductsData['qty_stock_after'] = $inventoryWarehouseProductSkus ? $inventoryWarehouseProductSkus['qty'] + (int) $sku['qty'] : 0;
                    }

                    $adjustmentProductsData['status'] = $sku['status'];
                    $adjustmentProductsData['note'] = isset($sku['note']) ? $sku['note'] : null;
                    $adjustmentProductsData['inventory_adjustment_id'] = $inventoryAdjustment->id;
                    array_push($adjustmentProductsArr, $adjustmentProductsData);
                    $adjustmentProduct = $this->inventoryAdjustmentProductRepository->create($adjustmentProductsData);

                    Log::info('*************************************');
                    // Store image
                    if (isset($sku['image']) && $sku['image'])
                        $this->saveImgBase64($sku, $adjustmentProduct);
                }
            }
        }
    }

    /**
     * @param mixed $inventoryAdjustment
     *
     * @return void
     */
    private function reserveQuantities($inventoryAdjustment)
    {
        // get needed data
        $inventoryAdjustmentProducts = $inventoryAdjustment->adjustmentProducts()->orderBy('id', 'desc')->get();

        // 4 cases [1 = Lost => decrease ,2 = Expired => decrease ,3 = Over Qty => incrase ,4 = Damaged => decrease ,5 = Return to Vendor => decrease]
        $this->decreaseTheStock($inventoryAdjustmentProducts, $inventoryAdjustment);

        // Handle Bundle Items
        $this->updateBundleQtyInAreaAndWarehouse($inventoryAdjustmentProducts, $inventoryAdjustment->area_id, $inventoryAdjustment->warehouse_id);
    }

    /**
     * @param mixed $request
     *
     * @return mixed
     */
    public function setStatus($request)
    {

        DB::beginTransaction();
        try {
            // [status = 0 => Canceled]
            $status = $request['status'];
            Log::info("Before Status" . $status);
            $inventoryAdjustment = $request['inventoryAdjustments'];
            // change status
            $inventoryAdjustment->update(['status' => $request['status']]);

            // get needed data
            $inventoryAdjustmentProducts = $inventoryAdjustment->adjustmentProducts()->orderBy('id', 'desc')->get();

            if ($status == InventoryAdjustment::STATUS_CANCELLED) {
                // In Case The Adjustment Cancelled, we will be return products qty
                $this->increaseTheStock($inventoryAdjustmentProducts, $inventoryAdjustment);
            }

            // [status = 2 => Approved]
            if ($status == InventoryAdjustment::STATUS_APPROVED) {
                // In Case The Adjustment Cancelled, we will be return products qty
                $this->handleOverQuantities($inventoryAdjustmentProducts, $inventoryAdjustment);

                // In Case there is Return Item to Vendor (submitted by Area Manger )
                $this->handleReturnToVendor($inventoryAdjustmentProducts, $inventoryAdjustment);
            }

            // Handle Bundle Items
            $this->updateBundleQtyInAreaAndWarehouse($inventoryAdjustmentProducts, $inventoryAdjustment->area_id, $inventoryAdjustment->warehouse_id);
            $admin_type = 'admin';
            $this->createAction($inventoryAdjustment, $request['admin_id'], $status, $admin_type);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ResponseErrorException(422, 'Something went wrong while updating status !');
        }
        return $inventoryAdjustment;
    }

    /**
     * @param mixed $inventoryAdjustmentProducts
     * @param mixed $inventoryAdjustment
     *
     * @return void
     */
    private function decreaseTheStock($inventoryAdjustmentProducts, $inventoryAdjustment)
    {

        foreach ($inventoryAdjustmentProducts as $row) {

            // Status == 3  => Over Qty  (Do Nothing)
            if ($row->status == InventoryAdjustmentProduct::STATUS_OVERQTY) {
                continue;
            }

            //  [1 = Lost ,2 = Expired ,4 = Damaged ] => (Decrease Qty)
            $inventoryProduct = InventoryProduct::where(['warehouse_id' => $inventoryAdjustment->warehouse_id]);
            $inventoryProductRow = $inventoryProduct->where(['sku' => $row['sku']])->first();
            $inventoryWarehouseRow = InventoryWarehouse::where(['warehouse_id' => $inventoryAdjustment->warehouse_id, 'product_id' => $row['product_id']])->first();
            $inventoryAreaRow = InventoryArea::where(['area_id' => $inventoryAdjustment->area_id, 'product_id' => $row['product_id']])->first();

            $finalQty = (($inventoryProductRow['qty'] - $row['qty']) >= 0) ? ($inventoryProductRow['qty'] - $row['qty']) : 0;
            $inventoryProductRow['qty'] = $finalQty;
            $inventoryProductRow['amount_before_discount'] = $finalQty * $inventoryProductRow['cost_before_discount'];
            $inventoryProductRow['amount'] = $finalQty * $inventoryProductRow['cost'];

            // Inventory Warehouses
            $inventoryWarehouseRow['qty'] = $inventoryWarehouseRow['qty'] - $row['qty'];

            // Inventory Areas
            $inventoryAreaRow['total_qty'] = $inventoryAreaRow['total_qty'] - $row['qty'];

            $inventoryProductRow->save();
            $inventoryWarehouseRow->save();
            $inventoryAreaRow->save();
        }
        // send and log stock problem if warehouseQty != totlSkuQt
        $this->logStockProblem($inventoryAdjustmentProducts, $inventoryAdjustment);
    }

    private function increaseTheStock($inventoryAdjustmentProducts, $inventoryAdjustment)
    {
        foreach ($inventoryAdjustmentProducts as $row) {

            // Status == 3  => Over Qty  (Do Nothing)
            if ($row->status == InventoryAdjustmentProduct::STATUS_OVERQTY) {
                continue;
            }

            $inventoryProduct = InventoryProduct::where(['warehouse_id' => $inventoryAdjustment->warehouse_id]);
            $inventoryProductRow = $inventoryProduct->where(['sku' => $row['sku']])->first();
            $inventoryWarehouseRow = InventoryWarehouse::where(['warehouse_id' => $inventoryAdjustment->warehouse_id, 'product_id' => $row['product_id']])->first();
            $inventoryAreaRow = InventoryArea::where(['area_id' => $inventoryAdjustment->area_id, 'product_id' => $row['product_id']])->first();

            // Inventory Products
            $finalQty = $inventoryProductRow['qty'] + $row['qty'];
            $inventoryProductRow['qty'] = $finalQty;
            $inventoryProductRow['amount_before_discount'] = $finalQty * $inventoryProductRow['cost_before_discount'];
            $inventoryProductRow['amount'] = $finalQty * $inventoryProductRow['cost'];

            // Inventory Warehouses
            $inventoryWarehouseRow['qty'] = $inventoryWarehouseRow['qty'] + $row['qty'];

            // Inventory Areas
            $inventoryAreaRow['total_qty'] = $inventoryAreaRow['total_qty'] + $row['qty'];

            $inventoryProductRow->save();
            $inventoryWarehouseRow->save();
            $inventoryAreaRow->save();
        }
    }

    /**
     * @param mixed $inventoryAdjustmentProducts
     * @param mixed $inventoryAdjustment
     * @param mixed $warehouseArea
     *
     * @return void
     */
    private function handleOverQuantities($inventoryAdjustmentProducts, $inventoryAdjustment)
    {
        foreach ($inventoryAdjustmentProducts as $row) {

            //  Status = 1 | 2 | 4 | 5 => Decrease Qty (Do Nothing)
            if ($row->status == InventoryAdjustmentProduct::STATUS_LOST || $row->status == InventoryAdjustmentProduct::STATUS_EXPIRED || $row->status == InventoryAdjustmentProduct::STATUS_DAMAGED || $row->status == InventoryAdjustmentProduct::STATUS_RETURN_TO_VENDOR) {
                continue;
            }

            $inventoryProduct = InventoryProduct::where(['warehouse_id' => $inventoryAdjustment->warehouse_id]);
            $inventoryProductRow = $inventoryProduct->where(['sku' => $row['sku']])->first();
            $inventoryWarehouseRow = InventoryWarehouse::where(['warehouse_id' => $inventoryAdjustment->warehouse_id, 'product_id' => $row['product_id']])->first();
            $inventoryAreaRow = InventoryArea::where(['area_id' => $inventoryAdjustment->area_id, 'product_id' => $row['product_id']])->first();

            // Inventory Products
            $finalQty = $inventoryProductRow['qty'] + $row['qty'];
            $inventoryProductRow['qty'] = $finalQty;
            $inventoryProductRow['amount_before_discount'] = $finalQty * $inventoryProductRow['cost_before_discount'];
            $inventoryProductRow['amount'] = $finalQty * $inventoryProductRow['cost'];

            // Inventory Warehouses
            $inventoryWarehouseRow['qty'] = $inventoryWarehouseRow['qty'] + $row['qty'];

            // Inventory Areas
            $inventoryAreaRow['total_qty'] = $inventoryAreaRow['total_qty'] + $row['qty'];

            $inventoryProductRow->save();
            $inventoryWarehouseRow->save();
            $inventoryAreaRow->save();
        }
    }

    public function handleReturnToVendor($inventoryAdjustmentProducts, $inventoryAdjustment)
    {
        $returnAmount = 0;
        foreach ($inventoryAdjustmentProducts as $row) {
            if ($row->status == InventoryAdjustmentProduct::STATUS_RETURN_TO_VENDOR) {
                // calculate amount to add to admin wallet who made this adjustment
                $amount = $this->getProductPrice($row);
                $returnAmount += $amount;
            }
        }
        $admin = auth('admin')->user();

        // add returnAmount TO Admin Walled
        $this->addMoneyToAreaManagerWallet($admin, $inventoryAdjustment, $returnAmount);
    }

    public function getProductPrice($row)
    {
        $purchaseOrderProduct = PurchaseOrderProduct::where(['product_id' => $row['product_id'], 'sku' => $row['sku']])->first();
        return $amount = isset($purchaseOrderProduct->cost) && $purchaseOrderProduct->cost ? $purchaseOrderProduct->cost * $row->qty : 0;
    }

    public function addMoneyToAreaManagerWallet(Admin $admin, InventoryAdjustment $inventoryAdjustment, $amount)
    {
        $admin->areaManagerAddMoneyFromAjdustment($amount, $inventoryAdjustment->area_id, $inventoryAdjustment->id);
    }

    public function createAction($inventoryAdjustment, $createdBy, $status, $admin_type)
    {

        switch ($status) {
            case InventoryAdjustment::STATUS_CANCELLED:
                $action = 'cancelled';
                break;
            case InventoryAdjustment::STATUS_PENDING:
                $action = 'pending';
                break;
            case InventoryAdjustment::STATUS_APPROVED:
                $action = 'approved';
                break;
        }
        $inventoryAdjustment->actions()->create([
            'action' => $action,
            'admin_type' => $admin_type,
            'admin_id' => $createdBy
        ]);
    }

    /**
     * @param mixed $inventoryAdjustmentProducts
     * @param mixed $areaID
     * @param mixed $wwarehouseID
     *
     * @return void
     */
    public function updateBundleQtyInAreaAndWarehouse($inventoryAdjustmentProducts, $areaID, $wwarehouseID)
    {
        $approvedItems = $inventoryAdjustmentProducts->pluck('product_id')->toArray();

        $bundles = Bundle::whereHas('areas', function ($query) use ($areaID) {
            $query->where('area_id', $areaID);
        })->whereHas('items', function ($query) use ($approvedItems) {
            $query->whereIn('product_id', $approvedItems);
        })->active()->get();

        $productBundles = Product::whereIn('bundle_id', $bundles->pluck('id')->toArray())->get();

        foreach ($productBundles as $product) {
            $productBundleItems = $product->bundleItems;
            // check qty stock in stock for product that is bundle
            $qtyInStock = [];
            foreach ($productBundleItems as $item) { // item in product bundle
                $invAreay = InventoryArea::where(['product_id' => $item['product_id'], 'area_id' => $areaID])->first();
                if ($invAreay) {
                    $invQty = $invAreay->total_qty;
                    $bundleQty = $item->quantity;
                    $qty = $invQty > 0 ? $invQty / $bundleQty : 0; // 15 / 4 = 3.75 = 3

                    array_push($qtyInStock, intval($qty));
                } else {
                    array_push($qtyInStock, 0);
                }
            }
            $totalInStock = min($qtyInStock);

            if ($totalInStock < 1) {

                // Set total_qty = 0 in area
                $productInInventoryArea = InventoryArea::where('product_id', $product['id'])->where('area_id', $areaID)->first();
                if ($productInInventoryArea) {
                    $productInInventoryArea->total_qty = 0;
                    $productInInventoryArea->save();
                }

                // Set qty = 0 in warehouse
                $productInInventoryWarehouse = InventoryWarehouse::where('product_id', $product['id'])->where('warehouse_id', $wwarehouseID)->where('area_id', $areaID)->first();
                if ($productInInventoryWarehouse) {
                    $productInInventoryWarehouse->qty = 0;
                    $productInInventoryWarehouse->save();
                }
            } else {
                // Set total_qty = 1 in area
                $productInInventoryArea = InventoryArea::where('product_id', $product['id'])->where('area_id', $areaID)->first();
                if ($productInInventoryArea) {
                    $productInInventoryArea->total_qty = 1;
                    $productInInventoryArea->save();
                }

                // Set qty = 1 in warehouse
                $productInInventoryWarehouse = InventoryWarehouse::where('product_id', $product['id'])->where('warehouse_id', $wwarehouseID)->where('area_id', $areaID)->first();
                if ($productInInventoryWarehouse) {
                    $productInInventoryWarehouse->qty = 1;
                    $productInInventoryWarehouse->save();
                }
            }
        }
    }

    public function logStockProblem($inventoryAdjustmentProducts, $inventoryAdjustment)
    {

        // check if there is problem in stock and cache it for review
        foreach ($inventoryAdjustmentProducts->unique('product_id') as $row) {
            $warehouseQty = InventoryWarehouse::where(['warehouse_id' => $inventoryAdjustment->warehouse_id, 'product_id' => $row['product_id']])->first()->qty;
            $totalSkuQty = InventoryProduct::where(['warehouse_id' => $inventoryAdjustment->warehouse_id, 'product_id' => $row['product_id']])->groupBy('warehouse_id', 'product_id')->sum('qty');

            if ($warehouseQty != $totalSkuQty) {
                $logData = [
                    'adjustment' => $inventoryAdjustment->id,
                    'product_id' => $row['product_id'],
                    'warehouse_qty ' => $warehouseQty,
                    'total_sku_qty' => $totalSkuQty,
                    'date' => Carbon::now()->toDateTimeString(),
                ];
                Log::info(['adjustment_' . $inventoryAdjustment->id => $logData]);
                logAdjustmentStockInCache($inventoryAdjustment->id, $row['product_id'], 'adjustment_' . $inventoryAdjustment->id, $logData);
            }
        }
    }

    public function getAdjustmentStatus($status)
    {
        // '1 => Lost, 2 => Expired, 3 => Over Qty, 3 => Damaged'
        $productStatus = '';
        if ($status == InventoryAdjustmentProduct::STATUS_LOST) {
            $productStatus = 'Lost';
        } elseif ($status == InventoryAdjustmentProduct::STATUS_EXPIRED) {
            $productStatus = 'Expired';
        } elseif ($status == InventoryAdjustmentProduct::STATUS_OVERQTY) {
            $productStatus = 'Over Qty';
        } elseif ($status == InventoryAdjustmentProduct::STATUS_DAMAGED) {
            $productStatus = 'Damaged';
        }
        return $productStatus;
    }
}