<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use Webkul\Area\Models\Area;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\Warehouse;

class InventoryWarehouseAmount
{

    public $name;
    protected $data;
    private $headings = ['Warehouse ID', 'Warehouse', 'Amount Before Discount', 'ِAmount'];
    private $areas;
    protected $remainingQty;

    public function __construct(array $data)
    {
        $this->name = "inventory-warehouse-amount";
        $this->data = $data;
        $areaId = $this->data['area'] ?? null;
        if ($areaId) {
            $this->areas = Area::where('id', $areaId)->get('id', 'name');
        } else {
            $this->areas = Area::get('id', 'name');
        }
    }

    public function getMappedQuery()
    {

        return $this->getMergedData();
    }

    public function getHeaddings()
    {
        return $this->headings;
    }

    public function getName()
    {
        return $this->name;
    }

    public function download()
    {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

    private function getMergedData()
    {
        $validWarehouse = $this->getWarehouseValid();
        $invalidWarehouse = $this->getWarehouseNotValid();

        $allWarehouses = $validWarehouse->merge($invalidWarehouse);
        $newCollection = collect();

        foreach ($allWarehouses as $warehouse) {

            if ($newCollection->where('warehouse_id', $warehouse['warehouse_id'])->isEmpty()) {

                $existWarehouse = $allWarehouses->where('warehouse_id', $warehouse['warehouse_id']);

                $item['warehouse_id'] = $warehouse['warehouse_id'];
                $item['w_name'] = $warehouse['w_name'];
                $item['amount_before_discount'] = $existWarehouse->sum('amount_before_discount');
                $item['amount'] = $existWarehouse->sum('amount');
                $newCollection->push($item);
            }
        }

        $total = [
            'warehouse_id' => 'Total Amount',
            'w_name' => '---------------------',
            'amount_before_discount' => $newCollection->sum('amount_before_discount'),
            'amount' => $newCollection->sum('amount')
        ];
        $newCollection->push($total);

        return $newCollection;
    }

    private function getWarehouseValid()
    {
        $lang = $this->data['lang'];

        $select = "SELECT 
        (SELECT 
                name
            FROM
            warehouse_translations
            WHERE
            warehouse_id = FINAL.warehouse_id and locale = '{$lang}') AS 'w_name',
        warehouse_id,
        SUM(inventory_value) AS 'amount',
        SUM(inventory_value_before_discount) AS 'amount_before_discount'
    FROM
        (SELECT 
            *,
                (SELECT 
                        qty
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
    GROUP BY FINAL.warehouse_id
    ";

        $select = preg_replace("/\r|\n/", "", $select);
        $select = preg_replace("/\t+/", "", $select);

        $query = collect(DB::select(DB::raw($select)));

        $validWarehouse = $query->map(
            function ($item) {

                $data['warehouse_id'] = $item->warehouse_id;
                $data['w_name'] = $item->w_name;
                $data['amount_before_discount'] = $item->amount_before_discount;
                $data['amount'] = $item->amount;

                return $data;
            },
            $query
        );
        return $validWarehouse;
    }

    private function getWarehouseNotValid()
    {
        $lang = $this->data['lang'];
        $select = "select IW.warehouse_id warehouse_id,IW.product_id product_id  
					from (
						(select warehouse_id ,product_id,qty warehouseQty
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
					order by  IW.warehouse_id, IW.product_id";

        $select = preg_replace("/\r|\n/", " ", $select);
        $select = preg_replace("/\t+/", " ", $select);

        $query = collect(DB::select(DB::raw($select)));
        $allItems = [];
        foreach ($query as $row) {
            $item = $this->getProductSku($row->warehouse_id, $row->product_id);
            $warehouse = Warehouse::find($row->warehouse_id);
            $allItems[] = ['warehouse_id' => $item[0]['warehouse_id'], 'w_name' => $warehouse->name ?? 'none', 'amount_before_discount' => $item->sum('amount_before_discount'), 'amount' => $item->sum('amount')];
        }

        $invalidWarehouse = collect($allItems);
        $invalidWarehouse = $invalidWarehouse->map(
            function ($item) {

                $data['warehouse_id'] = $item['warehouse_id'];
                $data['w_name'] = $item['w_name'];
                $data['amount_before_discount'] = $item['amount_before_discount'];
                $data['amount'] = $item['amount'];

                return $data;
            },
            $invalidWarehouse
        );
        return $invalidWarehouse;
    }

    // display correct sku qty in real team (adjustment create)
    private function getProductSku($warehouseID, $productID)
    {
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
                        'warehouse_id' => $warehouseID, 'product_id' => $inventorySku->product_id, 'sku' => $inventorySku->sku, 'qty' => ($inventorySku->qty + $int_part), 'cost_before_discount' => $inventorySku->cost_before_discount, 'cost' => (float) $inventorySku->cost, 'amount_before_discount' => (float) $inventorySku->cost_before_discount * ((int) $inventorySku->qty + (int) $int_part), 'amount' => (float) $inventorySku->cost * ((int) $inventorySku->qty + (int) $int_part)
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
                        'warehouse_id' => $warehouseID, 'product_id' => $inventorySku->product_id, 'sku' => $inventorySku->sku, 'qty' => $patch, 'cost_before_discount' => $inventorySku->cost_before_discount, 'cost' => $inventorySku->cost, 'amount_before_discount' => $inventorySku->cost_before_discount * $inventorySku->qty, 'amount' => $inventorySku->cost * $inventorySku->qty
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

    private function getNearestDividedNumber(int $number, int $count)
    {
        if ($number % $count != 0) {
            return $this->getNearestDividedNumber($number - 1, $count);
        }

        return $number;
    }
}
