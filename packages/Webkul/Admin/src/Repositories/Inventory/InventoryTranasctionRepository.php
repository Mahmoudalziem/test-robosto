<?php

namespace Webkul\Admin\Repositories\Inventory;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Webkul\Core\Eloquent\Repository;
use Webkul\Inventory\Models\Warehouse;
use Illuminate\Container\Container as App;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Purchase\Models\PurchaseOrderProduct;
use Webkul\Inventory\Contracts\InventoryTransaction;
use Webkul\Admin\Repositories\Product\ProductRepository;
use Webkul\Inventory\Models\InventoryTransaction as InventoryTransactionModel;
use Webkul\Sales\Models\OrderItemSku;
use Webkul\Bundle\Models\Bundle;
use Webkul\Bundle\Models\BundleItem;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ResponseErrorException;

class InventoryTranasctionRepository extends Repository {

    protected $inventoryTranasctionProductRepository;

    public function __construct(
            InventoryTranasctionProductRepository $InventoryTranasctionProductRepository,
            App $app
    ) {
        $this->inventoryTranasctionProductRepository = $InventoryTranasctionProductRepository;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model() {
        return InventoryTransaction::class;
    }

    public function list($request) {
        $query = $this->newQuery();
        $query = $query->fromAreaToAreaValidation();
        //$query = app(App\User::class)->newQuery()->with('group');
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
        if ($request->exists('transaction_type') && !empty($request['transaction_type'])) {
            $query->where('transaction_type', $request['transaction_type']);
        }

        // status  0 canceled /1 pending /2 on the way /3 transfered
        if ($request->exists('status') && !empty($request['status'])) {
            $query->where('status', $request['status']);
        }


        if ($request->exists('date_from') && !empty($request['date_from']) && $request->exists('date_to') && !empty($request['date_to'])) {
            $query->where(function ($q) use ($request) {
                $dateFrom = $request['date_from'] . ' 00:00:00';
                $dateTo = $request['date_to'] . ' 23:59:59';
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            });
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;

        $pagination = $query->with('fromWarehouse', 'toWarehouse')->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

    public function searchProduct($request) {
        // get prodcuts ids
        $productIds = Product::whereTranslationLike('name', "%" . $request['key'] . "%")->orWhere('barcode', $request['key'])->pluck('id')->toArray();

        return Product::whereHas('warehouses', function ($q) use ($productIds, $request) {
                    $q->where('warehouse_id', $request['from_warehouse_id'])
                            ->whereIn('product_id', $productIds);
                })->paginate(20);
    }

    public function selectProduct($request) {
        $productObj['product_id'] = $request['product']->id;
        $productObj['name'] = $request['product']->name;
        $productObj['image'] = $request['product']->image_url;
        $productObj['thumb'] = $request['product']->thumb_url;
        $productObj['source_stock'] = InventoryWarehouse::where(
                                ['product_id' => $request['product']->id, 'warehouse_id' => $request['from_warehouse_id']]
                        )
                        ->first() ? InventoryWarehouse::where(
                                ['product_id' => $request['product']->id, 'warehouse_id' => $request['from_warehouse_id']]
                        )
                        ->first()->qty : 0;
        $productObj['distance_stock'] = InventoryWarehouse::where(
                                ['product_id' => $request['product']->id, 'warehouse_id' => $request['to_warehouse_id']]
                        )
                        ->first() ? InventoryWarehouse::where(
                                ['product_id' => $request['product']->id, 'warehouse_id' => $request['to_warehouse_id']]
                        )
                        ->first()->qty : 0;
        $productObj['source_stock_details'] = InventoryProduct::where(
                        [
                            'warehouse_id' => $request['from_warehouse_id'],
                            'product_id' => $request['product']->id
                        ])->where('qty', '>', 0)
                ->get(['id as inventory_product_id', 'product_id', 'sku', 'qty']);
        return $productObj;
    }

    public function showProductSku($sku, $request) {

        // initial values
        $fromArea = Warehouse::find($request['from_warehouse_id'])->area_id;
        $details['purchasingQty'] = 0;
        $details['remainQty'] = 0;
        $details['soldQty'] = 0;
        $details['storeQty'] = 0;

        $purshaseProductOrders = PurchaseOrderProduct::where('sku', $sku)->first();

        if ($purshaseProductOrders) {
            $details['purchasingQty'] = $purshaseProductOrders->qty;
        }

        $inventoryProductSkus = InventoryProduct::where(['area_id' => $fromArea, 'sku' => $request['sku']])->get();
        if ($inventoryProductSkus) {
            $details['remainQty'] = $inventoryProductSkus->sum('qty');
        }

        // sold (purchasingQty  - remainQty)
        if ($details['remainQty'] && $details['purchasingQty']) {
            $details['soldQty'] = (int) OrderItemSku::where('sku', $request['sku'])->sum('qty');
        }

        $inventoryWarehouseProductSkus = InventoryProduct::where(['area_id' => $fromArea, 'warehouse_id' => $request['from_warehouse_id'], 'sku' => $request['sku']])->first();
        if ($inventoryWarehouseProductSkus) {
            $details['storeQty'] = $inventoryWarehouseProductSkus;
        }
        return $details;
    }

    public function deleteProduct($inventoryTransaction, $inventoryTransactionProduct) {

        // remove item from inventory transaction if status (pending,on_the_way)
        if ($inventoryTransaction->status == InventoryTransactionModel::STATUS_PENDING || $inventoryTransaction->status == InventoryTransactionModel::STATUS_ON_THE_WAY) {
            Log::info('Delete Sku Product ');
            // only return qty in area_from and warehouse_from 
            $areaFromID = $inventoryTransaction->from_area_id;
            $warehouseFromID = $inventoryTransaction->from_warehouse_id;
            DB::beginTransaction();
            try {
                // Update Inventroy Balance
                // inventory Products (from_werehouse=source warehouse)
                // incrase the qty of product of selected sku row(id of inventory_products) in the selected warehouse (source store)
                $inventoryProducts = InventoryProduct::where(['area_id' => $areaFromID, 'warehouse_id' => $warehouseFromID, 'sku' => $inventoryTransactionProduct->sku])->first();
                if ($inventoryProducts) {
                    $inventoryProducts['qty'] = $inventoryProducts['qty'] + $inventoryTransactionProduct->qty;
                    $inventoryProducts->save();
                }

                // inventory Warehouses
                $inventoryWarehouse = InventoryWarehouse::where(['area_id' => $areaFromID, 'warehouse_id' => $warehouseFromID, 'product_id' => $inventoryTransactionProduct->product_id])->first();
                $inventoryWarehouse['qty'] = $inventoryWarehouse['qty'] + $inventoryTransactionProduct->qty;
                $inventoryWarehouse->save();

                // inventory Areas
                $inventoryArea = InventoryArea::where(['area_id' => $areaFromID, 'product_id' => $inventoryTransactionProduct->product_id])->first();
                $inventoryArea['total_qty'] = $inventoryArea['total_qty'] + $inventoryTransactionProduct->qty;
                $inventoryArea->save();

                // remove item from transaction product
                $inventoryTransactionProduct->delete();

                // update Bundle Qty In Area And Warehouse
                $this->updateBundleQtyInAreaAndWarehouse($inventoryTransaction->transactionProducts, $areaFromID, $inventoryTransaction->from_warehouse_id);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw new ResponseErrorException(422, 'Something went wrong while deleting sku !');
            }
        }
        return $inventoryTransaction;
    }

    public function create(array $request) {
        DB::beginTransaction();
        try {
            Log::info('Create/Initiate Skus ');
            // master table data
            $transactionData = Arr::except($request, ['products']);
            $transactionData['status'] = InventoryTransactionModel::STATUS_PENDING; // pending
            $fromArea = Warehouse::find($request['from_warehouse_id'])->area_id;
            $toArea = Warehouse::find($request['to_warehouse_id'])->area_id;
            $transactionData['transaction_type'] = $fromArea == $toArea ? 'inside' : 'outside'; // if warehouse in same area (inside)
            $transactionData['from_area_id'] = $fromArea;
            $transactionData['to_area_id'] = $toArea;
            $transactionData['admin_id'] = $request['admin_id'];
            // Details Table data
            $ProductsData = $request['products'];
            $inventoryTransaction = $this->model->create($transactionData);
            // loop inside products
            foreach ($ProductsData as $row) {
                // loop inside skus
                foreach ($row['skus'] as $sku) {
                    if (isset($sku['qty']) && $sku['qty'] > 0) {

                        $transactionProductsData['inventory_product_id'] = $sku['inventory_product_id']; // to get the exaxt sku row
                        $transactionProductsData['product_id'] = $sku['product_id'];
                        $transactionProductsData['sku'] = $sku['sku'];
                        $transactionProductsData['qty'] = $sku['qty'];
                        $transactionProductsData['inventory_transaction_id'] = $inventoryTransaction->id;
                        $this->inventoryTranasctionProductRepository->create($transactionProductsData);

                        // Update Inventroy Balance
                        // inventory Products (from_werehouse)
                        // decrease the qty of product of selected sku row(id of inventory_products) in the selected warehouse (source store)
                        $inventoryProducts = InventoryProduct::where(['warehouse_id' => $request['from_warehouse_id'], 'sku' => $sku['sku']])->first();
                        if ($inventoryProducts) {
                            $inventoryProducts->qty = $inventoryProducts->qty - $sku['qty'];
                            $inventoryProducts->save();
                        }

                        // inventory Warehouses
                        $inventoryWarehouse = InventoryWarehouse::where(['warehouse_id' => $request['from_warehouse_id'], 'product_id' => $sku['product_id']])->first();
                        if ($inventoryWarehouse) {
                            $inventoryWarehouse->qty = $inventoryWarehouse->qty - $sku['qty'];
                            $inventoryWarehouse->save();
                        }

                        // inventory Areas
                        $inventoryArea = InventoryArea::where(['area_id' => $fromArea, 'product_id' => $sku['product_id']])->first();
                        if ($inventoryArea) {
                            $inventoryArea->total_qty = $inventoryArea->total_qty - $sku['qty'];
                            $inventoryArea->save();
                        }
                    }
                }
            }

            $this->updateBundleQtyInAreaAndWarehouse($inventoryTransaction->transactionProducts, $fromArea, $inventoryTransaction->from_warehouse_id);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ResponseErrorException(422, 'Something went wrong while creating transfer !');
        }
        return $inventoryTransaction;
    }

    public function setStatus($request) {
        DB::beginTransaction();
        try {
            $status = $request['status'];
            $inventoryTransactions = $request['inventoryTransactions'];
            // change status
            $inventoryTransactions->update(['status' => $status, 'admin_id' => $request['admin_id']]);

            // $status = 0 Canceled then increase source wharehouse (inventory products,inventory warehouses,inventory areas)
            if ($status == 0) {
                $this->setStatusCancelled($inventoryTransactions);
            }

            // $status ==1  pending do no thing now (no transaction needed)
            // $status ==2 on the way do no thing now (no transaction needed)
            // $status = 3
            // transferred then increase destination wharehouse (inventory products,inventory warehouses,inventory areas)
            if ($status == 3) {

                $this->setStatusTransfered($inventoryTransactions);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ResponseErrorException(422, 'Something went wrong while creating transfer !');
        }
        return $inventoryTransactions;
    }

    public function setStatusCancelled($inventoryTransactions) {
        Log::info('setStatus Cancelled');
        // get needed data
        $fromArea = $inventoryTransactions->fromWarehouse->area_id;
        // transferred then increase destination wharehouse (inventory products,inventory warehouses,inventory areas)
        $inventoryTransactionsProducts = $inventoryTransactions->transactionProducts;

        foreach ($inventoryTransactionsProducts as $row) {

            // Update Inventroy Balance
            // inventory Products (from_werehouse=source warehouse)
            // incrase the qty of product of selected sku row(id of inventory_products) in the selected warehouse (source store)
            $inventoryProducts = InventoryProduct::where(['warehouse_id' => $inventoryTransactions->from_warehouse_id, 'sku' => $row['sku']])->first();
            if ($inventoryProducts) {
                $inventoryProducts['qty'] = $inventoryProducts['qty'] + $row['qty'];
                $inventoryProducts->save();
            }


            // inventory Warehouses
            $inventoryWarehouse = InventoryWarehouse::where(['warehouse_id' => $inventoryTransactions->from_warehouse_id, 'product_id' => $row['product_id']])->first();
            if ($inventoryWarehouse) {
                $inventoryWarehouse['qty'] = $inventoryWarehouse['qty'] + $row['qty'];
                $inventoryWarehouse->save();
            }


            // inventory Areas
            $inventoryArea = InventoryArea::where(['area_id' => $fromArea, 'product_id' => $row['product_id']])->first();
            if ($inventoryArea) {
                $inventoryArea['total_qty'] = $inventoryArea['total_qty'] + $row['qty'];
                $inventoryArea->save();
            }
        }
        // update Bundle Qty In Area And Warehouse
        $this->updateBundleQtyInAreaAndWarehouse($inventoryTransactionsProducts, $fromArea, $inventoryTransactions->from_warehouse_id);

        return $inventoryTransactions;
    }

    public function setStatusTransfered($inventoryTransactions) {
        Log::info('setStatus Transfered');
        // get needed data
        $fromArea = $inventoryTransactions->fromWarehouse->area_id;
        $toArea = $inventoryTransactions->toWarehouse->area_id;
        // transferred then increase destination wharehouse (inventory products,inventory warehouses,inventory areas)
        $inventoryTransactionsProducts = $inventoryTransactions->transactionProducts;

        foreach ($inventoryTransactionsProducts as $row) {

            // transferred then increase destination wharehouse (inventory products,inventory warehouses,inventory areas)
            $inventoryProducts = InventoryProduct::where(['warehouse_id' => $inventoryTransactions->to_warehouse_id, 'sku' => $row['sku']])->first();
            if ($inventoryProducts) { // update
                $sumQty = $inventoryProducts['qty'] + $row['qty'];
                $inventoryProducts['qty'] = $sumQty;
                $inventoryProducts['amount_before_discount'] = $sumQty * $inventoryProducts['cost_before_discount'];
                $inventoryProducts['amount'] = $sumQty * $inventoryProducts['cost'];
                $inventoryProducts->save();
            } else { // create if not exist
                // get product sku details to save it on same row
                $inventoryProductObj = InventoryProduct::where(['warehouse_id' => $inventoryTransactions->from_warehouse_id, 'sku' => $row['sku']])->first();
                $inventoryProductInsert['product_id'] = $row['product_id'];
                $inventoryProductInsert['sku'] = $inventoryProductObj['sku'];
                $inventoryProductInsert['qty'] = $row['qty'];
                $inventoryProductInsert['prod_date'] = $inventoryProductObj['prod_date'];
                $inventoryProductInsert['exp_date'] = $inventoryProductObj['exp_date'];
                $inventoryProductInsert['cost_before_discount'] = $inventoryProductObj['cost_before_discount'];
                $inventoryProductInsert['cost'] = $inventoryProductObj['cost'];
                $inventoryProductInsert['amount_before_discount'] = $row['qty'] * $inventoryProductObj['cost_before_discount'];
                $inventoryProductInsert['amount'] = $row['qty'] * $inventoryProductObj['cost'];
                $inventoryProductInsert['warehouse_id'] = $inventoryTransactions->to_warehouse_id;
                $inventoryProductInsert['area_id'] = $toArea;
                InventoryProduct::create($inventoryProductInsert);
            }

            // inventory Warehouses
            $inventoryWarehouse = InventoryWarehouse::where(['warehouse_id' => $inventoryTransactions->to_warehouse_id, 'product_id' => $row['product_id']])->first();
            if ($inventoryWarehouse) { // update
                $inventoryWarehouse['qty'] = $inventoryWarehouse['qty'] + $row['qty'];
                $inventoryWarehouse->save();
            } else { // create if not exist
                $inventoryWarehouseInsert['product_id'] = $row['product_id'];
                $inventoryWarehouseInsert['qty'] = $row['qty'];
                $inventoryWarehouseInsert['warehouse_id'] = $inventoryTransactions->to_warehouse_id;
                $inventoryWarehouseInsert['area_id'] = $toArea;
                $inventoryWarehouseInsert['can_order'] = $inventoryTransactions->toWarehouse->is_main ? 0 : 1;
                InventoryWarehouse::create($inventoryWarehouseInsert);
            }

            // inventory Areas
            $inventoryArea = InventoryArea::where(['area_id' => $toArea, 'product_id' => $row['product_id']])->first();
            if ($inventoryArea) { // update
                $inventoryArea['total_qty'] = $inventoryArea['total_qty'] + $row['qty'];
                $inventoryArea->save();
            } else { // create if not exist
                $inventoryAreaInsert['product_id'] = $row['product_id'];
                $inventoryAreaInsert['init_total_qty'] = $row['qty'];
                $inventoryAreaInsert['total_qty'] = $row['qty'];
                $inventoryAreaInsert['area_id'] = $toArea;
                InventoryArea::create($inventoryAreaInsert);
            }
        }
        // update Bundle Qty In Area And Warehouse
        $this->updateBundleQtyInAreaAndWarehouse($inventoryTransactionsProducts, $toArea, $inventoryTransactions->to_warehouse_id);

        return $inventoryTransactions;
    }

    public function updateBundleQtyInAreaAndWarehouse($inventoryTransactionsProducts, $areaID, $warehouseID) {
        $approvedItems = $inventoryTransactionsProducts->pluck('product_id')->toArray();
        $bundles = Bundle::whereHas('areas', function ($query) use ($areaID) {
                    $query->where('area_id', $areaID);
                })->whereHas('items', function ($query) use ($approvedItems) {
                    $query->whereIn('product_id', $approvedItems);
                })->active()->get();

        $productBundles = Product::whereIn('bundle_id', $bundles->pluck('id')->toArray())->get();
        foreach ($productBundles as $product) { // product (type = bundle)
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
                $productInInventoryWarehouse = InventoryWarehouse::where('product_id', $product['id'])->where('warehouse_id', $warehouseID)->where('area_id', $areaID)->first();
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
                $productInInventoryWarehouse = InventoryWarehouse::where('product_id', $product['id'])->where('warehouse_id', $warehouseID)->where('area_id', $areaID)->first();
                if ($productInInventoryWarehouse) {
                    $productInInventoryWarehouse->qty = 1;
                    $productInInventoryWarehouse->save();
                }
            }
        }
    }

}
