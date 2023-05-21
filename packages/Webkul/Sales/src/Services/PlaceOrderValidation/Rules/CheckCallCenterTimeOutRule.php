<?php

namespace Webkul\Sales\Services\PlaceOrderValidation\Rules;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\PlaceOrderValidationException;
use Webkul\Sales\Services\PlaceOrderValidation\PlaceOrderRule;

class CheckCallCenterTimeOutRule extends PlaceOrderRule
{
    /**
     * @array $data
     */
    private $data;

    /**
     * @param arary $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Check If callcenter can place the order before time out
     * 
     * @param array $items
     * @return mixed
     */
    public function check(array $items)
    {
        $cacheKey = "callcenter_{$this->data['call_enter']}_place_order_for_customer_{$this->data['customer_id']}_after";
        $getCachedTime = Cache::get($cacheKey);
        
        if ($getCachedTime < now()) {
            throw new PlaceOrderValidationException(410, __('admin::app.checkItemsAgain'));
        }

        return parent::check($items);
    }
}
