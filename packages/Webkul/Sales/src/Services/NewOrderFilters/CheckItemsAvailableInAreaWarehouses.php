<?php

namespace Webkul\Sales\Services\NewOrderFilters;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Inventory\Models\InventoryWarehouse;
use Webkul\Inventory\Models\Warehouse;

class CheckItemsAvailableInAreaWarehouses
{
    private $items;
    private $areaId;
    private $updating;

    /**
     * CheckItemsAvailable constructor.
     * @param array $items
     * @param int $areaId
     * @param bool $updating
     */
    public function __construct(array $items, int $areaId, bool $updating = false)
    {
        $this->items = collect($items)->where('qty_shipped', '>', 0)->toArray();
        $this->areaId = $areaId;
        $this->updating = $updating;
    }

    /**
     * @return mixed
     */
    public function getAllWarehousesHaveItems()
    {
        if ( count($this->items) == 0 && $this->updating ) {
            return [
                'items_found'   =>  true
            ];
        }

        $data = $this->runQuery();
        $warehousesWithProducts = $this->handleQueryResponse($data);

        return $this->checkItemsAreAvailable($warehousesWithProducts);
    }

    /**
     * @return mixed
     */
    public function runQuery()
    {
        $orderItemsArray = collect($this->items)->pluck('product_id')->toArray();
        $orderItemsArray = implode(',', $orderItemsArray);
        // Get Warehouses that have the items
        $data =  DB::select(DB::raw("SELECT warehouse_id ,
                    GROUP_CONCAT(product_id SEPARATOR '-') as product_ids ,
                    GROUP_CONCAT(qty SEPARATOR '-') as products_qty
                    FROM inventory_warehouses
                    WHERE qty > 0 AND product_id in ({$orderItemsArray}) AND area_id = {$this->areaId}
                    GROUP BY warehouse_id"));
        return $data;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function handleQueryResponse(array $data)
    {
        $warehouses = [];
        foreach ($data as $item) {
            $itemData = (array) $item;
            $products = explode('-', $itemData['product_ids']);
            $productsQty = explode('-', $itemData['products_qty']);
            
            $warehouseFromDB = Warehouse::find($itemData['warehouse_id']);
            if ($warehouseFromDB->status == 0) {
                continue;
            }

            $warehouses[] = [
                'warehouse_id' =>  $itemData['warehouse_id'],
                'items_count'     =>  count($products),
                'qty_count'     =>  array_sum($productsQty),
                'products'  =>  $this->fillArrayWithKeysAndValues($products, $productsQty),
            ];
        }

        return $warehouses;
    }

    /**
     * @param $products
     * @param $productsQty
     * @return mixed
     */
    public function fillArrayWithKeysAndValues($products, $productsQty)
    {
        $productsWithQty = [];

        for ($i = 0; $i < count($products); $i++) {
            $productsWithQty[] = [
                'id'    =>  $products[$i],
                'qty'    =>  $productsQty[$i],
            ];
        }

        return $productsWithQty;
    }

    /**
     * Check All Items are available in warehouse or NOT
     * 
     * @param array $allWarehousesHaveItems
     * @return array
     */
    public function checkItemsAreAvailable(array $allWarehousesHaveItems)
    {
        $warehousesHaveItems = [];

        if (count($allWarehousesHaveItems) == 0) {
            return [
                'items_found'   =>  false,
                'warehouses'    =>  []
            ];
        }

        // Get all warehouses have items or Some them 
        foreach ($allWarehousesHaveItems as $warehouse) {
            $warehousesHaveItems[$warehouse['warehouse_id']] = ['warehouse_id'  =>  $warehouse['warehouse_id'],  'have_items'  =>  true];
            foreach ($this->items as $item) {
                if (collect($warehouse['products'])->where('id', $item['product_id'])->where('qty', '>=', $item['qty_shipped'])->isEmpty()) {
                    $warehousesHaveItems[$warehouse['warehouse_id']]['have_items'] = false;
                    break;
                }
            }
        }

        // just, get warehouses have all items
        $warehousesThatHaveItems = collect($warehousesHaveItems)->where('have_items', true);

        // if there is no one warehouse has all items, then go to UnHappy Scenario
        if ($warehousesThatHaveItems->isEmpty()) {
            // UnHappy
            return [
                'items_found'   =>  false,
                'warehouses'    =>  $this->getWarehouseWithHighestItems($allWarehousesHaveItems)
            ];
        }
        
        // Else, Happy Scenario, -> the order is ready to dispatch
        return [
            'items_found'   =>  true,
            'warehouses'    =>  $warehousesThatHaveItems->pluck('warehouse_id')->toArray()
        ];
    }

    /**
     * Get Warehouse that have Highest Items
     *
     * @param array $allWarehousesHaveItems
     * @return array|mixed
     */
    public function getWarehouseWithHighestItems(array $allWarehousesHaveItems)
    {
        $highestCountItems = max(array_column($allWarehousesHaveItems, 'items_count'));
        $highestCountQty = max(array_column($allWarehousesHaveItems, 'qty_count'));
        $checkWarehousesHaveAllItems = true;

        // check that all warehouses have all items Regardless Quantity
        foreach ($allWarehousesHaveItems as $warehouse) {
            if ($warehouse['items_count'] != count($this->items)) {
                $checkWarehousesHaveAllItems = false;
                break;
            }
        }

        $highestWarehousesHaveItems = [];
        foreach ($allWarehousesHaveItems as $warehouse) {
            // if all warehouses have all items, then filter by [ all items and highest items quantity ]
            if ($checkWarehousesHaveAllItems) {
                if ($warehouse['items_count'] == $highestCountItems && $warehouse['qty_count'] == $highestCountQty) {
                    $highestWarehousesHaveItems = $warehouse;
                    break;
                }
            } else {
                // if all warehouses does not have all items, then filter by [ highest exist items ]
                if ($warehouse['items_count'] == $highestCountItems) {
                    $highestWarehousesHaveItems = $warehouse;
                    break;
                }
            }
        }
        return $highestWarehousesHaveItems;
    }

    /**
     * Get Not-Enough Items and Out-Of-Stock Items From Warehouse that have Highest Items
     *
     * @param array $highestWarehouse
     * @return array|mixed
     */
    public function handleWarehouseWithHighestItems(array $highestWarehouse)
    {
        $allItems = [
            'not_enough'    =>  [],
            'out_of_stock'    =>  []
        ];

        if (count($highestWarehouse) == 0) {
            return [
                'warehouse_id' =>  null,
                'items'     =>  $allItems
            ];
        }

        $warehouseProducts = collect($highestWarehouse['products']);

        foreach ($this->items as $item) {
            // if item Exist in Warehouse Items
            $itemExist = $warehouseProducts->where('id', $item['product_id'])->first();

            if ($itemExist) {
                if ($itemExist['qty'] < $item['qty_shipped']) {
                    $allItems['not_enough'][] = [
                        'product_id'        =>  $item['product_id'],
                        'available_qty'     =>  $itemExist['qty']
                    ];
                }
            } else {
                $allItems['out_of_stock'][] = [
                    'product_id'        =>  $item['product_id'],
                ];
            }

        }

        return [
            'warehouse_id' =>  $highestWarehouse['warehouse_id'],
            'items'     =>  $allItems
        ];
    }


}