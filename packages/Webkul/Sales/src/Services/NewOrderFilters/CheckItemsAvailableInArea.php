<?php

namespace Webkul\Sales\Services\NewOrderFilters;

use Illuminate\Support\Facades\Log;
use Webkul\Inventory\Models\InventoryArea;

class CheckItemsAvailableInArea
{
    private $items;
    private $areaId;

    /**
     * CheckItemsAvailable constructor.
     * @param array $items
     * @param int $areaId
     */
    public function __construct(array $items, int $areaId)
    {
        $this->items = $items;
        $this->areaId = $areaId;
    }

    /**
     * @return mixed
     */
    public function checkProductInInventory()
    {
        $availableItems = [];
        $outOfStockItems = [];
        foreach ($this->items as $item) {
            // check that this product is available and Quantity enough
            $product = InventoryArea::where([
                'product_id'    =>   $item['id'],
                'area_id'       =>      $this->areaId
            ])->where('total_qty', '>=', $item['qty'])->first();

            if ($product) {
                $availableItems[] = $item;
            } else {
                $outOfStockItems[] = $item;
            }
        }

        return [
            'availableItems'    =>  $availableItems,
            'outOfStockItems'    =>  $outOfStockItems,
        ];
    }


}