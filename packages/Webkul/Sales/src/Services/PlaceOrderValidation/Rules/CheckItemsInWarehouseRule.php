<?php

namespace Webkul\Sales\Services\PlaceOrderValidation\Rules;


use App\Exceptions\PlaceOrderValidationException;
use Webkul\Sales\Services\PlaceOrderValidation\PlaceOrderRule;
use Webkul\Sales\Services\NewOrderFilters\CheckItemsAvailableInAreaWarehouses;

class CheckItemsInWarehouseRule extends PlaceOrderRule
{
    
    /**
     * @int $areaID
     */
    private $areaID;

    /**
     * @bool $updating
     */
    private $updating;

    /**
     * Tags constructor.
     * @param int $areaID
     */
    public function __construct(int $areaID, bool $updating = false)
    {
        $this->areaID = $areaID;
        $this->updating = $updating;
    }

    /**
     * Check Promotion Valid from date to date
     * @param array $items
     * @return mixed
     */
    public function check(array $items)
    {
        
        $items = $this->prepareItemsForChecking($items);
        $checkItemsAvailableInWarehouses = new CheckItemsAvailableInAreaWarehouses($items, $this->areaID, $this->updating);
        $allWarehousesHaveItems = $checkItemsAvailableInWarehouses->getAllWarehousesHaveItems();

        // First of All Check Items area available in one warehouse
        if ($allWarehousesHaveItems['items_found'] == false) {
            throw new PlaceOrderValidationException(410, __('sales::app.itemsNotAvailable'));
        }
        
        return parent::check($items);
    }


    /**
     * @param array $items
     * @return array
     */
    private function prepareItemsForChecking(array $items)
    {
        $newItems = [];
        foreach ($items as $item) {
            $newItems[] = [
                'product_id' => $item['id'],
                'qty_ordered' => $item['qty'],
                'qty_shipped' => $item['qty']
            ];
        }

        return $newItems;
    }
}