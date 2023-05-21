<?php

namespace Webkul\Sales\Services\PlaceOrderValidation\Rules;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use App\Exceptions\PlaceOrderValidationException;
use Webkul\Sales\Services\PlaceOrderValidation\PlaceOrderRule;

class CheckManyOrdersAtTimeRule extends PlaceOrderRule
{
    /**
     * @var Customer
     */
    private $customer;

    /**
     * Tags constructor.
     * @param Customer $customer
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Check Customer cannot Place Order within 30 minutes
     * @param array $items
     * @return mixed
     */
    public function check(array $items)
    {
        $seconds = config('robosto.MANY_ORDER_WITHIN');

        $customerPendingOrder = $this->customer->pendingOrders;
        
        if ($customerPendingOrder->isNotEmpty() && Carbon::parse($customerPendingOrder->first()->created_at)->addSeconds($seconds)->timestamp > now()->timestamp) {
            
            throw new PlaceOrderValidationException(410, __('sales::app.cannotCreateManyOrderAtTime', ['seconds'    =>  $seconds]));
        }

        return parent::check($items);
    }
}