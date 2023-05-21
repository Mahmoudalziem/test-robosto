<?php

namespace Webkul\Sales\Services\PlaceOrderValidation\Rules;

use Carbon\Carbon;
use Webkul\Sales\Models\PaymentMethod;
use App\Exceptions\PlaceOrderValidationException;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Sales\Services\PlaceOrderValidation\PlaceOrderRule;

class PaymentMethodRule extends PlaceOrderRule
{
    /**
     * @var array
     */
    private $data;

    /**
     * Tags constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Check Customer cannot Place Order within 30 minutes
     * @param array $items
     * @return mixed
     */
    public function check(array $items)
    {

        $data = $this->data;
        // if schedule time gived
        if (isset($data['payment_method_id']) && !empty($data['payment_method_id']) ) {

            $payment = PaymentMethod::find($data['payment_method_id']);
            // in case of that payment method is credit card and Card ID not provided
            if ($payment->slug == OrderPayment::CREDIT_CARD && (!isset($data['card_id']) || empty($data['card_id'])) ) {
                throw new PlaceOrderValidationException(410, __('sales::app.cardIdRequired')); 
            }
        }

        return parent::check($items);
    }
}