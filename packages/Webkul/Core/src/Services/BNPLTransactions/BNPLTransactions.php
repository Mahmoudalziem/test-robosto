<?php

namespace Webkul\Core\Services\BNPLTransactions;

use Carbon\Carbon;
use Webkul\Customer\Models\BuyNowPayLater;
use Webkul\Customer\Models\Customer;

class BNPLTransactions
{

    /**
     * @return bool
     */
    public function applyBNPLPayments()
    {
        $payments = BuyNowPayLater::where('status','pending')->where('release_date' ,'<=', Carbon::now())->get();
        foreach ($payments as $payment){
            $customer = Customer::find($payment->customer_id);
            $customer->subtractBNPLMoney($payment->amount,$payment->order_id);
            $payment->update(['status'=>'released']);
        }
    }
}
