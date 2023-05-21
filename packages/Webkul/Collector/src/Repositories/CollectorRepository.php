<?php

namespace Webkul\Collector\Repositories;

use Carbon\Carbon;
use Illuminate\Container\Container as App;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use Webkul\Admin\Repositories\Inventory\InventoryAdjustmentProductRepository;
use Webkul\Admin\Repositories\Inventory\InventoryAdjustmentRepository;
use Webkul\Admin\Repositories\Inventory\InventoryTranasctionRepository;
use Webkul\Collector\Http\Resources\Order\OrderSingle;
use Webkul\Collector\Models\Collector;
use Webkul\Core\Eloquent\Repository;
use Webkul\Driver\Models\Driver;
use Webkul\Product\Models\Product;
use Webkul\Inventory\Models\ProductStock;
use Webkul\Inventory\Models\InventoryControl;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\InventoryTransaction;
use Webkul\Inventory\Models\InventoryAdjustment;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Core\Services\SendPushNotification;
use Illuminate\Support\Facades\Log;

class CollectorRepository extends Repository {

    protected $orderRepository;
    protected $inventoryTranasctionRepository;
    protected $inventoryAdjustmentRepository;
    protected $inventoryAdjustmentProductRepository;

    /**
     * Create a new repository instance.
     *
     * @param OrderRepository $orderRepository
     * @param App $app
     */
    public function __construct(
            OrderRepository $orderRepository,
            InventoryTranasctionRepository $inventoryTranasctionRepository,
            InventoryAdjustmentRepository $inventoryAdjustmentRepository,
            InventoryAdjustmentProductRepository $inventoryAdjustmentProductRepository,
            App $app) {
        $this->orderRepository = $orderRepository;
        $this->inventoryTranasctionRepository = $inventoryTranasctionRepository;
        $this->inventoryAdustmentRepository = $inventoryAdjustmentRepository;
        $this->inventoryAdjustmentProductRepository = $inventoryAdjustmentProductRepository;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model() {
        return Collector::class;
    }

    public function archivedOrders($collectorWarehouse) {

        $date = Carbon::today()->toDateString();
        return Order::whereDate('created_at', '=', $date)->whereNotIn('status', [Order::STATUS_PENDING, Order::STATUS_PREPARING, Order::STATUS_READY_TO_PICKUP])->paginate(15);
    }

    /**
     * @param int $collectorId
     *
     * @return mixed
     */
    public function currentOrder($collector) {

        $currentOrders = $this->orderRepository->findWhere(['collector_id' => $collector->id, 'status' => Order::STATUS_PREPARING, 'warehouse_id' => $collector->warehouse_id]);
        // if(count($currentOrders) == 0 ){
        //     $currentOrders = $this->orderRepository->findWhere(['status' => Order::STATUS_PREPARING,'warehouse_id'=>$collector->warehouse_id]);
        // }
        return $currentOrders;
    }

    // Inventory
    // list inventory products
    public function inventoryProductsList($warehouseId) {

        // get all transfers for collector [from_warehouse_id || to_warehouse_id]
        return InventoryWarehouse::where(['warehouse_id' => $warehouseId])->where('qty', '>', 0)
                        ->paginate(15);
    }

    public function inventoryProductOne($product) {
        return InventoryWarehouse::where(['product_id' => $product->id, 'warehouse_id' => auth()->user()->warehouse->id])->first();
    }

    // list all transfers(invetnory transaction)
    // where from_warehouse_id or to_warehouse_id
    public function tasks($warehouseId) {

        // get all transfers for collector [from_warehouse_id || to_warehouse_id]
        return InventoryTransaction::whereIn('status', [1, 2])
                        ->where(function ($q) use ($warehouseId) {
                            $q->where('from_warehouse_id', $warehouseId)
                            ->orWhere('to_warehouse_id', $warehouseId);
                        })->paginate(15);
    }

    // show  transfers(invetnory transaction || Adjustment)
    public function taskShow($id) {

        // get all transfers for collector [from_warehouse_id || to_warehouse_id]
        return $this->inventoryTranasctionRepository->findOrFail($id);
    }

    public function transactionSetStatusOnTheWay($transaction, $taskType) {
        if ($taskType == "Out") {
            $this->inventoryTranasctionRepository->update(['status' => InventoryTransaction::STATUS_ON_THE_WAY], $transaction->id);
        }
    }

    public function transactionSetStatusTransfered($transaction, $taskType) {
        if ($taskType == "In") {
            $this->inventoryTranasctionRepository->update(['status' => InventoryTransaction::STATUS_TRANSFERRED], $transaction->id);
            // start add inventory stock to destination warehouse(to_warehouse_id)
            $this->inventoryTranasctionRepository->setStatusTransfered($transaction);
        }
    }

    // accept driver id , period , date_status_log,status
    public function setStatusLog($data, $collector) {

        $type = $data['type'];

        if ($type == 'online' || $type == 'offline') {
            //dd($type);
            $collector->update([
                'is_online' => $type == 'online' ? 1 : 0,
                'can_receive_orders' => $type == 'online' ? '1' : '0'
            ]);
            $collector->loginLogs()->create(['action' => $type]);
        }

        $collector->update(['availability' => $type]);
        return $collector;
    }

    public function newReturnAdjustment($order, $data) {
        return $this->createAdustment($data);
    }

    public function createAdustment($data) {


        $adjustmentData = $data['adjustmentData']; // pending
        // Details Table data
        $adjustmentProductsArr = array();
        $ProductsData = $data['items'];
        $inventoryAdjustment = InventoryAdjustment::create($adjustmentData);
        foreach ($ProductsData as $row) {
            $adjustmentProductsData['product_id'] = $row['product_id'];
            $adjustmentProductsData['sku'] = $row['sku'];
            $adjustmentProductsData['qty'] = $row['qty'];
            $adjustmentProductsData['status'] = $row['status'];
            $adjustmentProductsData['note'] = $row['status'];
            $adjustmentProductsData['inventory_adjustment_id'] = $inventoryAdjustment->id;
            array_push($adjustmentProductsArr, $adjustmentProductsData);
            $this->inventoryAdjustmentProductRepository->create($adjustmentProductsData);
        }

        return $inventoryAdjustment;
    }

    // stock scan item
    public function postItemStock($data) {
        $inventoryControl = InventoryControl::where('area_id', $data['area_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->where('is_active', 1)
                ->first();

        if (!$inventoryControl) {
            return false;
        }
        // if collector has been scanned the same product for the active inventory control

        $productFound = ProductStock::where('product_id', $data['product_id'])
                ->where('inventory_control_id', $inventoryControl['id']);
        // we update all is defaul = 0
        $productFound->update(['is_default' => 0]);

        $subQry = " ( SELECT sum(qty_shipped) FROM `order_items` od_its "
                . " inner join orders ON od_its.order_id = orders.id "
                . "where od_its.product_id = " . $data['product_id'] . " "
                . "and orders.status = '" . Order::STATUS_PREPARING . "' "
                . "and orders.area_id = " . $data['area_id'] . " "
                . "and orders.warehouse_id = " . $data['warehouse_id'] . " ) ";

        $qtyStock = InventoryWarehouse::
                selectRaw('area_id,warehouse_id,product_id,inventory_warehouses.qty inventory_qty  , COALESCE( ' . $subQry . ' , 0)as shipped_qty ')
                ->where('inventory_warehouses.product_id', $data['product_id'])
                ->where('area_id', $data['area_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->first();

        if ($qtyStock) {
            $itemStock = [
                'inventory_control_id' => $inventoryControl['id'],
                'product_id' => $data['product_id'],
                'area_id' => $qtyStock['area_id'],
                'warehouse_id' => $data['warehouse_id'],
                'inventory_qty' => $qtyStock['inventory_qty'],
                'shipped_qty' => $qtyStock['shipped_qty'],
                'qty' => $qtyStock['shipped_qty'] + $qtyStock['inventory_qty'], // from db [invenetory warehouse]
                'qty_stock' => $data['qty_stock'], // from Collector post
                'valid' => ($qtyStock['shipped_qty'] + $qtyStock['inventory_qty']) == $data['qty_stock'] ? 1 : 0,
                'status' => $data['qty_stock'] || $data['qty_stock'] == 0 ? 1 : 0
            ];
        } else {
            $itemStock = [
                'inventory_control_id' => $inventoryControl['id'],
                'product_id' => $data['product_id'],
                'area_id' => $data['area_id'],
                'warehouse_id' => $data['warehouse_id'],
                'inventory_qty' => 0,
                'shipped_qty' => $subQry ?? 0, // $subQry is shipped_qty
                'qty' => $subQry ?? 0 + 0, // from db [invenetory warehouse]
                'qty_stock' => $data['qty_stock'], // from Collector post
                'valid' => ($subQry ?? 0 + 0 ) == $data['qty_stock'] ? 1 : 0,
                'status' => $data['qty_stock'] || $data['qty_stock'] == 0 ? 1 : 0
            ];
        }
        $this->createNewStockForNotExistProduct($itemStock);

        return ProductStock::create($itemStock);
    }

    public function createNewStockForNotExistProduct($data) {
        // create new stock for not exist product in area
        $invArea = InventoryArea::where(['area_id' => $data['area_id'], 'product_id' => $data['product_id']]);
        // prodcut not exists in select area
        if ($invArea->count() == 0) {
            // create new entry for this product in this area
            InventoryArea::create([
                'area_id' => $data['area_id'],
                'product_id' => $data['product_id'],
                'init_total_qty' => 0,
                'total_qty' => 0,
            ]);
        }
        //////////////////////////////////////////////////////////
        // create new stock for not exist product in warehouse
        $invWarehouse = InventoryWarehouse::where(['area_id' => $data['area_id'], 'warehouse_id' => $data['warehouse_id'], 'product_id' => $data['product_id']]);
        // prodcut not exists in select warehouse
        if ($invWarehouse->count() == 0) {
            // create new entry for this product in this warehouse
            InventoryWarehouse::create([
                'area_id' => $data['area_id'],
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'qty' => 0,
            ]);
        }
        //////////////////////////////////////////////////////////
        // create new stock sku for not exist product in inventory product
        $invProduct = InventoryProduct::where(['area_id' => $data['area_id'], 'warehouse_id' => $data['warehouse_id'], 'product_id' => $data['product_id']]);
        // prodcut not exists in select warehouse
        if ($invProduct->count() == 0) {
            // find the hieghest sku of this product in whole inventory
            $maxSkuProduct = InventoryProduct::where(['product_id' => $data['product_id']])
                    ->orderBy('qty', 'DESC')
                    ->first();
            if ($maxSkuProduct) {
                // create new entry for this product in this warehouse
                InventoryProduct::create([
                    'area_id' => $data['area_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'product_id' => $data['product_id'],
                    'sku' => $maxSkuProduct->sku,
                    'prod_date' => $maxSkuProduct->prod_date,
                    'exp_date' => $maxSkuProduct->exp_date,
                    'qty' => 0,
                    'cost' => 0,
                    'amount_before_discount' => 0,
                    'amount' => 0,
                ]);
            }
        }
    }

    public function endInventoryControl($data) {

        // check if all items has been scaned and controlled
        // select all items where not in product stocks
        $inventoryControl = $data['inventory_control'];
        $productStocksAll = ProductStock::where('area_id', $data['area_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->where('inventory_control_id', $inventoryControl->id)
                ->where('status', 1)
                ->where('is_default', 1);
        $productStocks = $productStocksAll->pluck('product_id')->toArray();
        $data['productStocksAll'] = $productStocksAll->get()->toArray();

        // if there is scanned items then we create draft adjustment
        // prepare date to send to create a pending adjustment
        $inventoryAdjustmentRequest = $this->handleProductStockData($data);
        // request new pending adjustment
        $this->inventoryAdustmentRepository->create($inventoryAdjustmentRequest);

        $itemsNotScanned = InventoryWarehouse::whereNotIn('product_id', $productStocks)
                ->where('area_id', $data['area_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->where('qty', '>', 0)
                ->orderBy('product_id');

        foreach ($itemsNotScanned->get() as $product) {

            $subQry = " ( SELECT sum(qty_shipped) FROM `order_items` od_its "
                    . " inner join orders ON od_its.order_id = orders.id "
                    . "where od_its.product_id = " . $product['product_id'] . " "
                    . "and orders.status = '" . Order::STATUS_PREPARING . "' "
                    . "and orders.area_id = " . $data['area_id'] . " "
                    . "and orders.warehouse_id = " . $data['warehouse_id'] . " ) ";

            $qtyStock = InventoryWarehouse::
                    selectRaw('area_id,warehouse_id,product_id,inventory_warehouses.qty inventory_qty  , COALESCE( ' . $subQry . ' , 0)as shipped_qty ')
                    ->where('inventory_warehouses.product_id', $product['product_id'])
                    ->where('area_id', $data['area_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->where('qty', '>', 0)
                    ->first();

            $itemStock = [
                'inventory_control_id' => $inventoryControl->id,
                'product_id' => $product['product_id'],
                'area_id' => $data['area_id'],
                'warehouse_id' => $data['warehouse_id'],
                'inventory_qty' => $product['qty'],
                'shipped_qty' => $qtyStock['shipped_qty'],
                'qty' => $qtyStock['shipped_qty'] + $product['qty'], // from db [invenetory warehouse]
                'qty_stock' => 0, // from Collector post
                'valid' => 0,
                'status' => 0,
            ];

            ProductStock::create($itemStock);
        }
        return $inventoryControl;
    }

    private function handleProductStockData($data) {
        $adjustmentData['warehouse_id'] = $data['warehouse_id'];
        $adjustmentData['admin_id'] = $data['collector_id'];
        $adjustmentData['admin_type'] = 'collector';
        $adjustmentData['is_inventory_control'] = 1;
        $invQty = 0;

        // loop through all product stock posted by collector
        foreach ($data['productStocksAll'] as $keyProd => $product) {

            $invQty = $product['qty'];

            // list all available skus of product
            $skusInInventoryProduct = InventoryProduct::where('product_id', $product['product_id'])
                    ->where('area_id', $data['area_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    // ->where('qty', '>', 0)
                    ->orderBy('exp_date')
                    ->get();

            $netQty = 0;
            $remainQty = false;
            $isOverQty = false;
            $qtyStock = $product['qty_stock'];
            Log::info('start request');
            Log::info('===================');
            Log::info('product_id : ###    sku   ###    :    qty    :    type    ');
            foreach ($skusInInventoryProduct as $keySKU => $skuRow) {


                if ($isOverQty == false) {
                    if ($qtyStock > $invQty) { // over qty
                        $netQty = $remainQty == false ? ( $qtyStock - $invQty) : $netQty;
                        if ($netQty > 0) {

                            $adjustmentData['products'][$keyProd]['skus'][$keySKU]['status'] = 3; // over qty
                            $adjustmentData['products'][$keyProd]['skus'][$keySKU]['product_id'] = $product['product_id'];
                            $adjustmentData['products'][$keyProd]['skus'][$keySKU]['sku'] = $skuRow['sku'];
                            $adjustmentData['products'][$keyProd]['skus'][$keySKU]['qty'] = $netQty;
                            $adjustmentData['products'][$keyProd]['skus'][$keySKU]['note'] = 'Over qty by collector';
                            $remainQty = true;
                            $isOverQty = true;
                            $netQty = 0;
                            Log::info($product['product_id'] . '        : ' . $skuRow['sku'] . '   :    ' . $netQty . '    :  ' . '    over qty    ');
                            continue;
                        }
                    } elseif ($qtyStock < $invQty) { // lost
                        $netQty = $remainQty == false ? ($invQty - $qtyStock) : $netQty;
                        if ($netQty > 0) {
                            $adjustmentData['products'][$keyProd]['skus'][$keySKU]['status'] = 1; // lost
                            $adjustmentData['products'][$keyProd]['skus'][$keySKU]['product_id'] = $product['product_id'];
                            $adjustmentData['products'][$keyProd]['skus'][$keySKU]['sku'] = $skuRow['sku'];
                            if ($netQty >= $skuRow['qty']) {
                                $qty = $skuRow['qty'];
                                $netQty = $netQty - $qty;
                            } else {
                                $qty = $netQty;
                                $netQty = 0;
                            }
                            $remainQty = true;

                            $adjustmentData['products'][$keyProd]['skus'][$keySKU]['qty'] = $qty;
                            $adjustmentData['products'][$keyProd]['skus'][$keySKU]['note'] = 'Lost qty by collector';
                            Log::info($product['product_id'] . '        : ' . $skuRow['sku'] . '  :     ' . $netQty . '   :      ' . '    Lost    ');
                            $isOverQty = false;
                        }
                    }
                }
            }
            if ($netQty == 0)
                continue;
        }
        Log::info($adjustmentData);
        return $adjustmentData;
    }

    public function createAdjustment(array $request) {
        // master table data

        $area = Warehouse::find($request['warehouse_id'])->area_id;
        $adjustmentData = Arr::except($request, ['products']);
        $adjustmentData['status'] = 1; // pending
        $adjustmentData['area_id'] = $area;
        // Details Table data
        $adjustmentProductsArr = array();
        $ProductsData = $request['products'];

        $inventoryAdjustment = $this->model->create($adjustmentData);
        foreach ($ProductsData as $row) {
            foreach ($row['skus'] as $sku) {
                if ((isset($sku['qty']) && (int) $sku['qty'] > 0) && (isset($sku['status']) && $sku['status'] != 0)) {

                    $adjustmentProductsData['product_id'] = $sku['product_id'];
                    $adjustmentProductsData['sku'] = $sku['sku'];
                    $inventoryWarehouseProductSkus = InventoryProduct::where(['warehouse_id' => $request['warehouse_id'], 'sku' => $sku['sku']])->first();
                    $adjustmentProductsData['qty'] = $sku['qty'];

                    $adjustmentProductsData['qty_stock_before'] = $inventoryWarehouseProductSkus ? $inventoryWarehouseProductSkus['qty'] : 0;
                    if ($sku['status'] == 1 || $sku['status'] == 2 || $sku['status'] == 4) { //
                        $adjustmentProductsData['qty_stock_after'] = $inventoryWarehouseProductSkus ? $inventoryWarehouseProductSkus['qty'] - (int) $sku['qty'] : 0;
                    }
                    if ($sku['status'] == 3) {

                        $adjustmentProductsData['qty_stock_after'] = $inventoryWarehouseProductSkus ? $inventoryWarehouseProductSkus['qty'] + (int) $sku['qty'] : 0;
                    }

                    $adjustmentProductsData['status'] = $sku['status'];
                    $adjustmentProductsData['note'] = isset($sku['note']) ? $sku['note'] : null;
                    $adjustmentProductsData['inventory_adjustment_id'] = $inventoryAdjustment->id;
                    array_push($adjustmentProductsArr, $adjustmentProductsData);
                    $adjustmentProduct = $this->inventoryAdjustmentProductRepository->create($adjustmentProductsData);
                    // Store image
                    if (isset($sku['image']) && $sku['image'])
                        $this->saveImgBase64($sku, $adjustmentProduct);
                }
            }
        }

        return $inventoryAdjustment;
    }

}
