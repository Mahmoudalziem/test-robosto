<?php

namespace Webkul\Sales\Repositories\Traits;

use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Sales\Models\PaymentMethod;
use Webkul\Sales\Models\Order as OrderModel;
use Webkul\Core\Services\Payment\Paymob\PaymobService;
use Webkul\Sales\Repositories\Traits\OrderNotifications;

/**
 * Send Notifications to Drivers, Collectors, Customers
 */
trait OrderPayViaCC {

    use OrderNotifications;

    /**
     * @param OrderModel $order
     * @param array $data
     * @return bool
     * @throws InvalidOptionsException
     */
    public function payOrderPriceViaCC(OrderModel $order) {
        logOrderActionsInCache($order->id, 'start_in_pay_via_cc');

        // Get Order Payment Method
        $paymentMethod = $order->payment;
        Log::info("Start Charge Via Card from function ".$order->id."----->".($paymentMethod->method != OrderPayment::CREDIT_CARD || is_null($paymentMethod->paymob_card_id)));
        if ($paymentMethod->method != OrderPayment::CREDIT_CARD || is_null($paymentMethod->paymob_card_id)) {
            return false;
        }

        Log::info("Start Charge Via Card  from function  ".$order->id);
        $paymob = new PaymobService($order->customer, false);
        $response = $paymob->chargeViaCardToken($order->final_total, $order, $order->payment->paymob_card_id);

        Log::info("Pay Res");
        Log::info($response);

        if (isset($response['response']) && isset($response['response']['success'])) {
            $success = $response['response']['success'];
            if ($success == 'true') {
                // Update Order To Paid
                $this->updateOrderToPaid($order);
                return true;
            }
        }

        $this->updateOrderToNotPaid($order);

        $dataToCustomer = [
            'title' => __('sales::notifications.invalid_payment_via_card.title'),
            'body' => __('sales::notifications.invalid_payment_via_card.body'),
        ];
        $this->sendNotificationToCustomer($order, $dataToCustomer);

        return true;
    }

    /**
     * @param OrderModel $order
     *
     * @return bool
     */
    private function updateOrderToPaid(OrderModel $order) {
        // Update Order To Paid
        $order->is_paid = OrderModel::ORDER_PAID;
        $order->paid_type = OrderModel::PAID_TYPE_CC;
        $order->save();

        return true;
    }

    /**
     * @param OrderModel $order
     *
     * @return bool
     */
    public function updateOrderToNotPaid(OrderModel $order) {
        // Convert Order To Cache On Delivery [ COD ]
        $payment = PaymentMethod::where('slug', OrderPayment::CASH_ON_DELIVERY)->first();
        $order->payment()->update([
            'method' => $payment->slug,
            'payment_method_id' => $payment->id,
            'paymob_card_id' => null
        ]);

        return true;
    }

}
