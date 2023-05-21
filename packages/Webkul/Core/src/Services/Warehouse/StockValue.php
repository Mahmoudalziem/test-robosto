<?php

namespace Webkul\Core\Services\Warehouse;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Area\Models\Area;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Inventory\Models\InventoryStockValue;
use Carbon\Carbon;

class StockValue {

    protected $remainingQty;
    protected $createdAt;

    public function __construct() {

        $this->createdAt = Carbon::now()->toDateString();
    }

    /**
     * @return void
     */
    public function startBuild() {
        DB::beginTransaction();
        try {

            // Start Fixing
            $this->createWarehouseStockValue();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();
    }

    private function createWarehouseStockValue() {
        $inventoryStockValue = InventoryStockValue::where('build_date', $this->createdAt)->first();
        if (!$inventoryStockValue) {
            foreach ($this->getMergedData() as $row) {
                Log::alert($row);
                InventoryStockValue::create($row);
            }
        }
    }

    private function getMergedData() {
        $validWarehouse = $this->getWarehouseValid();
        $invalidWarehouse = $this->getWarehouseNotValid();
        $allWarehouses = $validWarehouse->merge($invalidWarehouse);
        Log::info($validWarehouse);
        $newCollection = collect();

        foreach ($allWarehouses as $warehouse) {

            if ($newCollection->where('warehouse_id', $warehouse['warehouse_id'])->isEmpty()) {

                $existWarehouse = $allWarehouses->where('warehouse_id', $warehouse['warehouse_id']);

                $item['area_id'] = $warehouse['area_id'];
                $item['warehouse_id'] = $warehouse['warehouse_id'];
                $item['amount_before_discount'] = $existWarehouse->sum('amount_before_discount');
                $item['amount'] = $existWarehouse->sum('amount');
                $item['build_date'] = $this->createdAt;
                $newCollection->push($item);
            }
        }
        return $newCollection;
    }

    private function getWarehouseValid() {
        $select = "SELECT
        (SELECT
                area_id
            FROM
                warehouses
            WHERE
                id = warehouse_id) AS 'area_id',
        warehouse_id,
        SUM(inventory_value) AS 'amount',
        SUM(inventory_value_before_discount) AS 'amount_before_discount'
    FROM
        (SELECT
            *,
                (SELECT
                        SUM(qty)
                    FROM
                        inventory_warehouses
                    WHERE
                        warehouse_id = BASE.warehouse_id
                            AND product_id = BASE.product_id) AS 'inventory_qty'
        FROM
            (SELECT
            product_id,
                warehouse_id,
                SUM(qty * cost) AS 'inventory_value',
                SUM(qty * cost_before_discount) AS 'inventory_value_before_discount',
                SUM(qty) AS 'product_sku_qty'
        FROM
            inventory_products
        WHERE
            product_id NOT IN (1544 , 1683, 1682, 1681, 1680, 1798, 1799, 1800)
                AND product_id NOT IN (SELECT
                    product_id
                FROM
                    product_sub_categories
                WHERE
                    sub_category_id IN (39 , 40, 64))
        GROUP BY product_id , warehouse_id) BASE
        HAVING inventory_qty = product_sku_qty) FINAL
    GROUP BY FINAL.warehouse_id";

        $select = preg_replace("/\r|\n/", "", $select);
        $select = preg_replace("/\t+/", "", $select);
        $query = collect(DB::select(DB::raw($select)));
        $validWarehouse = $query->map(
                function ($item) {
                    $data['area_id'] = $item->area_id;
                    $data['warehouse_id'] = $item->warehouse_id;
                    $data['amount_before_discount'] = $item->amount_before_discount;
                    $data['amount'] = $item->amount;
                    $data['build_date'] = $this->createdAt;

                    return $data;
                }, $query
        );
        return $validWarehouse;
    }

    private function getWarehouseNotValid() {
        $select = "select IW.area_id warehouse_id,IW.warehouse_id warehouse_id,IW.product_id product_id  ,warehouseQty,skuQty
					from (
						(select area_id,warehouse_id ,product_id,qty warehouseQty
						from inventory_warehouses
						order by warehouse_id,product_id) as IW
					inner join (
						SELECT warehouse_id,product_id,sum(qty) skuQty
						FROM inventory_products
						group by warehouse_id,product_id
						order by warehouse_id,product_id) IP
					on IW.warehouse_id =IP.warehouse_id and IW.product_id =IP.product_id )
					where warehouseQty != skuQty
                                                                                and warehouseQty > 0
					AND IW.product_id NOT IN (1544 , 1683, 1682, 1681, 1680, 1798, 1799, 1800)
					AND IW.product_id NOT IN (SELECT product_id FROM
									product_sub_categories
									WHERE sub_category_id IN (39 , 40, 64))
					order by  IW.warehouse_id, IW.product_id;";

        $select = preg_replace("/\r|\n/", " ", $select);
        $select = preg_replace("/\t+/", " ", $select);

        $query = collect(DB::select(DB::raw($select)));
        $allItems = [];
        foreach ($query as $row) {
            $item = $this->getProductSku($row->warehouse_id, $row->product_id);
            $allItems[] = ['area_id' => $item[0]['area_id'], 'warehouse_id' => $item[0]['warehouse_id'], 'amount_before_discount' => $item->sum('amount_before_discount'), 'amount' => $item->sum('amount')];
        }

        $invalidWarehouse = collect($allItems);
        $invalidWarehouse = $invalidWarehouse->map(
                function ($item) {
                    $data['area_id'] = $item['area_id'];
                    $data['warehouse_id'] = $item['warehouse_id'];
                    $data['amount_before_discount'] = $item['amount_before_discount'];
                    $data['amount'] = $item['amount'];
                    $data['build_date'] = $this->createdAt;
                    return $data;
                }, $invalidWarehouse
        );
        return $invalidWarehouse;
    }

    // display correct sku qty in real team (adjustment create)
    private function getProductSku($warehouseID, $productID) {
        $productObj = [];

        $inventoryWarehous = InventoryWarehouse::where(['warehouse_id' => $warehouseID, 'product_id' => $productID])->first();
        $warehouseQty = $inventoryWarehous ? $inventoryWarehous->qty : 0;

        $inventoryProduct = InventoryProduct::where(['warehouse_id' => $warehouseID, 'product_id' => $productID]);
        $totalSkuQty = $inventoryProduct->sum('qty');

        $inventorySkus = $inventoryProduct->orderBy('exp_date')->get();

        // if warehouse qty != skuqty
        if ($totalSkuQty != $warehouseQty) {

            // get qty that must be distributing
            $distributedQty = $warehouseQty - $totalSkuQty;
            //dd($distributedQty);

            $this->remainingQty = 0;
            $updatedSouceStockDetails = [];
            if ($totalSkuQty != 0) {

                foreach ($inventorySkus as $inventorySku) {
                    $distibutedPerent = (float) ($inventorySku->qty / $totalSkuQty);
                    $patch = $distributedQty * $distibutedPerent;
                    $int_part = (int) $patch; // only integer allowed for qty to be updated
                    $dec_part = fmod($patch, 1); // the decimal part will be summed then add the total integer in max qty sku
                    $this->remainingQty += $dec_part;
                    $updatedSouceStockDetails[] = [
                        'area_id' => $inventorySku->area_id, 'warehouse_id' => $warehouseID, 'product_id' => $inventorySku->product_id, 'sku' => $inventorySku->sku, 'qty' => ( $inventorySku->qty + $int_part ), 'cost_before_discount' => $inventorySku->cost_before_discount, 'cost' => (float) $inventorySku->cost, 'amount_before_discount' => (float) $inventorySku->cost_before_discount * ( (int) $inventorySku->qty + (int) $int_part ), 'amount' => (float) $inventorySku->cost * ( (int) $inventorySku->qty + (int) $int_part )
                    ];
                }
            }

            if ($totalSkuQty == 0) {
                $dividedNumber = $this->getNearestDividedNumber($warehouseQty, $inventorySkus->count());
                // 247 - 244 = 3
                $this->remainingQty = $warehouseQty - $dividedNumber;
                // 244 / 4 = 61
                $patch = $dividedNumber / $inventorySkus->count();
                foreach ($inventorySkus as $inventorySku) {
                    $updatedSouceStockDetails[] = [
                        'area_id' => $inventorySku->area_id, 'warehouse_id' => $warehouseID, 'product_id' => $inventorySku->product_id, 'sku' => $inventorySku->sku, 'qty' => $patch, 'cost_before_discount' => $inventorySku->cost_before_discount, 'cost' => $inventorySku->cost, 'amount_before_discount' => $inventorySku->cost_before_discount * $inventorySku->qty, 'amount' => $inventorySku->cost * $inventorySku->qty
                    ];
                }
            }
            $updatedSouceStockDetails = collect($updatedSouceStockDetails);

            if ($this->remainingQty) {
                $minQtyRow = $updatedSouceStockDetails->sortBy('qty')->first();
                $minQtyRow['qty'] = $minQtyRow['qty'] + round($this->remainingQty);
                $minQtyRow['amount'] = $minQtyRow['qty'] * $minQtyRow['cost'];
                $minQtyRow['amount_before_discount'] = $minQtyRow['qty'] * $minQtyRow['cost_before_discount'];
                $updatedSouceStockDetails = $updatedSouceStockDetails->map(function ($item, $key) use ($minQtyRow) {
                    if ($item['sku'] == $minQtyRow['sku']) {
                        $item = $minQtyRow;
                    }

                    return $item;
                });
            }
            //rearange by exp_date
            //$array_column = array_column($updatedSouceStockDetails, 'exp_date');
            // array_multisort($array_column, SORT_ASC, $updatedSouceStockDetails);
            $productObj = $updatedSouceStockDetails;
        }

        return $productObj;
    }

    private function getNearestDividedNumber(int $number, int $count) {
        if ($number % $count != 0) {
            return $this->getNearestDividedNumber($number - 1, $count);
        }

        return $number;
    }

}
