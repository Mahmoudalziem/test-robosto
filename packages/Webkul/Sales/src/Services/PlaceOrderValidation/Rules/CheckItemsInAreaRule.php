<?php

namespace Webkul\Sales\Services\PlaceOrderValidation\Rules;


use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use App\Exceptions\PlaceOrderValidationException;
use Webkul\Sales\Services\PlaceOrderValidation\PlaceOrderRule;
use Webkul\Sales\Services\NewOrderFilters\CheckItemsAvailableInArea;

class CheckItemsInAreaRule extends PlaceOrderRule
{
    /**
     * @var Customer
     */
    private $customer;

    /**
     * @int $areaID
     */
    private $areaID;

    /**
     * Tags constructor.
     * @param Customer $customer
     */
    public function __construct(Customer $customer, int $areaID)
    {
        $this->customer = $customer;
        $this->areaID = $areaID;
    }

    /**
     * Check Promotion Valid from date to date
     * @param array $items
     * @return mixed
     */
    public function check(array $items)
    {
        if(count($items)==0){
            throw new PlaceOrderValidationException(410, __('sales::app.itemsNotAvailable'));
        }
        // First of All Check Items area available in Area
        $checkItemsInArea = new CheckItemsAvailableInArea($items, $this->areaID);
        $checkItems = $checkItemsInArea->checkProductInInventory();

        if (count($checkItems['outOfStockItems']) != 0) {

            
            throw new PlaceOrderValidationException(410, __('sales::app.itemsNotAvailable'));
        }

        return parent::check($items);
    }
}
