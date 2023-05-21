<?php

namespace Webkul\Core\Services\FixSKUs;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\InventoryWarehouse;

class FixSkus
{

    protected $remainingQty;

    /**
     * @return void
     */
    public function startFix()
    {
        DB::beginTransaction();
        try {

            // Start Fixing
            $this->fixProducts();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();
    }

    /**
     * @return void
     */
    private function fixProducts()
    {
        $inventoryWarehouses = $this->getTargetProducts();

        foreach ($inventoryWarehouses as $inventoryWarehouse) {

            $inventorySkus = InventoryProduct::where([
                ['product_id', '=', $inventoryWarehouse->product_id],
                ['warehouse_id', '=', $inventoryWarehouse->warehouse_id],
                ['area_id', '=', $inventoryWarehouse->area_id]
            ])->select(['id', 'sku', 'qty'])->get();

            $this->fixPerProduct($inventoryWarehouse, $inventorySkus);
        }
    }

    /**
     * @return Collection
     */
    private function getTargetProducts()
    {
        $stm = "SELECT IW.id, area_id, warehouse_id, IW.product_id, qty, sku_qty FROM inventory_warehouses IW
                INNER JOIN
                    (SELECT
                        warehouse_id AS ipw, product_id, SUM(qty) AS sku_qty
                        FROM inventory_products
                        GROUP BY warehouse_id , product_id
                    ) IP ON IP.ipw = IW.warehouse_id AND IP.product_id = IW.product_id
                WHERE qty != sku_qty";

        // Run SQl Query to get products that have different quantity in warehouse from skus
        $data = collect(DB::select(DB::raw($stm)));

        return $data;
    }

    /**
     * @param mixed $inventoryWarehouse
     * @param EloquentCollection $inventorySkus
     *
     * @return void
     */
    private function fixPerProduct($inventoryWarehouse, EloquentCollection $inventorySkus)
    {
        $totalSkuQty = (int) $inventoryWarehouse->sku_qty;

        if ($inventorySkus->isNotEmpty() && $totalSkuQty != $inventoryWarehouse->qty && $inventoryWarehouse->qty > 0) {

            Log::info(' inventorySkussum qty:  ' . $totalSkuQty);
            $distributedQty = $inventoryWarehouse->qty - $totalSkuQty;  // 448 - 446 = 2
            Log::info('distributedQty:  ' . $distributedQty);

            $this->remainingQty = 0;
            if ($totalSkuQty != 0) {
                foreach ($inventorySkus as $inventorySku) {
                    $distibutedPerent = (float) ($inventorySku->qty / $totalSkuQty);
                    $patch = $distributedQty * $distibutedPerent;

                    $int_part = (int) $patch;
                    $dec_part = fmod($patch, 1);
                    $this->remainingQty += $dec_part;

                    Log::info('distibutedPerent: ' . $distibutedPerent);
                    Log::info('patch: ' . $patch . ' int_p:' . $int_part . ' dec_p:' . $dec_part . ' remain:' . $this->remainingQty);
                    Log::info('b4 prod:' . $inventoryWarehouse->product_id . ' sku: ' . $inventorySku->sku . ' qty: ' . $inventorySku->qty . ' total  qty:' . $inventorySkus->sum('qty') . ' store qty:' . $inventoryWarehouse->qty);

                    $inventorySku->qty = $inventorySku->qty + $int_part;
                    $inventorySku->save();
                    Log::info('aftr prd:' . $inventoryWarehouse->product_id . ' sku: ' . $inventorySku->sku . ' qty: ' . $inventorySku->qty . ' total  qty:' . $inventorySkus->sum('qty') . ' store qty:' . $inventoryWarehouse->qty);
                }
            }

            if ($totalSkuQty == 0) {
                $dividedNumber = $this->getNearestDividedNumber($inventoryWarehouse->qty, $inventorySkus->count());
                // 247 - 244 = 3
                $this->remainingQty = $inventoryWarehouse->qty - $dividedNumber;
                // 244 / 4 = 61
                $patch = $dividedNumber / $inventorySkus->count();
                foreach ($inventorySkus as $inventorySku) {
                    $inventorySku->qty = $patch;
                    $inventorySku->save();
                }
            }


            if ($this->remainingQty) {

                $maxQtySKU = $inventorySkus->sortByDesc('qty')->first();
                $maxQtySKU->qty = $maxQtySKU->qty + round($this->remainingQty);
                $maxQtySKU->save();
            }
        }

        if ($inventorySkus->isNotEmpty() && $totalSkuQty > 0 && $inventoryWarehouse->qty == 0) {
            foreach ($inventorySkus as $inventorySku) {
                $inventorySku->qty = 0;
                $inventorySku->save();
            }
        }
    }

    // display correct sku qty in real team (adjustment create)
    public function getProductSku($warehouseID, $productID)
    {
        $productObj = [];

        $inventoryWarehous = InventoryWarehouse::where(['warehouse_id' => $warehouseID, 'product_id' => $productID])->first();
        $warehouseQty = $inventoryWarehous ? $inventoryWarehous->qty : 0;

        $inventoryProduct = InventoryProduct::where(['warehouse_id' => $warehouseID, 'product_id' => $productID]);
        $totalSkuQty = $inventoryProduct->sum('qty');

        $inventorySkus = $inventoryProduct->orderBy('exp_date', 'desc')->get(['id as inventory_product_id', 'product_id', 'sku', 'qty', 'exp_date']);

        // if warehouse qty != skuqty
        if ($inventorySkus->isNotEmpty() && ($totalSkuQty != $warehouseQty)) {

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
                    $updatedSouceStockDetails[] = ['inventory_product_id' => $inventorySku->inventory_product_id, 'product_id' => $inventorySku->product_id, 'sku' => $inventorySku->sku, 'qty' => ($inventorySku->qty + $int_part), 'exp_date' => $inventorySku->exp_date];
                }
            }

            if ($totalSkuQty == 0) {
                $dividedNumber = $this->getNearestDividedNumber($warehouseQty, $inventorySkus->count());
                // 247 - 244 = 3
                $this->remainingQty = $warehouseQty - $dividedNumber;
                // 244 / 4 = 61
                $patch = $dividedNumber / $inventorySkus->count();
                foreach ($inventorySkus as $inventorySku) {
                    $updatedSouceStockDetails[] = ['inventory_product_id' => $inventorySku->inventory_product_id, 'product_id' => $inventorySku->product_id, 'sku' => $inventorySku->sku, 'qty' => $patch, 'exp_date' => $inventorySku->exp_date];
                }
            }

            if ($this->remainingQty) {
                // find the max qty
                $array_column = array_column($updatedSouceStockDetails, 'qty');
                array_multisort($array_column, SORT_ASC, $updatedSouceStockDetails);
                // add the remaining qty to the max qty
                $updatedSouceStockDetails[0]['qty'] = $updatedSouceStockDetails[0]['qty'] + round($this->remainingQty);
            }
            //rearange by exp_date
            $array_column = array_column($updatedSouceStockDetails, 'exp_date');
            array_multisort($array_column, SORT_DESC, $updatedSouceStockDetails);
            $productObj['skuItems'] = $updatedSouceStockDetails;
        } else {
            $productObj['skuItems'] = $inventorySkus;
        }
        $productObj['warehouseQty'] = $warehouseQty;
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