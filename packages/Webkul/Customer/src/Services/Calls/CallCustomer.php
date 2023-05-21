<?php
namespace Webkul\Customer\Services\Calls;

use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;

class CallCustomer
{

    /**
     * @param Customer $customer
     * @param Order|null $order
     * 
     * @return bool
     */
    public function orderWaitingCall(Customer $customer, Order $order = null)
    {
        if ($order->status != Order::STATUS_WAITING_CUSTOMER_RESPONSE ) {
            Log::alert("Order Status is not Waiting, so we will not call the customer");
            return false;
        }

        $phone = $customer->phone;
        $this->callCustomer($phone, 102);

        return true;
    }

    /**
     * @param string $phone
     * @param int $otp
     * 
     * @return void
     */
    public function callCustomerWithOtp($phone, $otp)
    {
        Log::info("Call Customer ". $phone . " With OTP -> " . $otp);

        $url = 'https://api-gateway.innocalls.com/api/order-confirmation';

        $headers = [
            'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpc3MiOiJodHRwczpcL1wvZ2xvY29tc3lzdGVtcy5jb20iLCJhdWQiOiJodHRwczpcL1wvZ2xvY29tc3lzdGVtcy5jb20iLCJpYXQiOjEzNTY5OTk1MjQsIm5iZiI6MTM1Njk5OTUyNCwiaWQiOiI0YjhmNGNmYi1jMTgwLTQ5NGYtODVmOS1iNjYwY2M2YzA0OWQiLCJnY2kiOiJhYjQ3YWYwYi1mMzcyLTQxOTItYjc4OC04OWVhYTJjYzQ0ZTMifQ.U4t2CkzUFTqNZ1v0bZ9I-AVq88QSvUGz5J_MtdJeFfTFNITCBJYsuvbdjNHZP3pEpwG-DwX7qwzbfGOCmp77Zw',
            'Cookie: __cfduid=daad03eb7971ddd1911b7555cbe085dc81617009241; DO-LB=node-187928191|YGmNG|YGmMH',
            'Content-Type: application/json'
        ];

        $data = [
            'phone' => '2' . $phone,
            'call_flow_id' => '62a0c71435b64100a9d642cc',
            'order_number' => $otp,
            'type'  => 'order_cost',
            'order_cost'    => 0,
            'order_currency'    => "SAR",
        ];

        requestWithCurl($url, 'POST', $data, $headers);
    }


    /**
     * @param string $phone
     * @param int $soundId
     * 
     * @return void
     */
    private function callCustomer($phone, $soundId = 76)
    {
        Log::info("Call Customer -> " . $phone);
        $url = 'https://api-gateway.innocalls.com/api/call-campaign/v1/call';
        $headers = [
            'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpc3MiOiJodHRwczpcL1wvZ2xvY29tc3lzdGVtcy5jb20iLCJhdWQiOiJodHRwczpcL1wvZ2xvY29tc3lzdGVtcy5jb20iLCJpYXQiOjEzNTY5OTk1MjQsIm5iZiI6MTM1Njk5OTUyNCwiaWQiOiI0YjhmNGNmYi1jMTgwLTQ5NGYtODVmOS1iNjYwY2M2YzA0OWQiLCJnY2kiOiJhYjQ3YWYwYi1mMzcyLTQxOTItYjc4OC04OWVhYTJjYzQ0ZTMifQ.U4t2CkzUFTqNZ1v0bZ9I-AVq88QSvUGz5J_MtdJeFfTFNITCBJYsuvbdjNHZP3pEpwG-DwX7qwzbfGOCmp77Zw',
            'Cookie: __cfduid=daad03eb7971ddd1911b7555cbe085dc81617009241; DO-LB=node-187928191|YGmNG|YGmMH',
            'Content-Type: application/json'
        ];
        $data = [
            'phone' => '2' . $phone,
            'call_flow_id' => '5ee8caa982147900208dedd4',
            'sound_id'  =>  $soundId
        ];

        requestWithCurl($url, 'POST', $data, $headers);
    }
}