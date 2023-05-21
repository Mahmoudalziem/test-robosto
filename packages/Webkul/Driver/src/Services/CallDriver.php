<?php
namespace Webkul\Driver\Services;

use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\Log;
use Webkul\Driver\Models\Driver;

class CallDriver
{
    /**
     * @param Driver $driver
     * @param Order|null $order
     * 
     * @return bool
     */
    public function orderAtPlaceCall(Driver $driver, Order $order = null)
    {
        if ($order->status != Order::STATUS_AT_PLACE ) {
            return false;
        }

        if ($driver->phone_work) {
            $this->callDriver($driver->phone_work, 104);
        }

        return true;
    }

    /**
     * @param string $phone
     * @param int $soundId
     * 
     * @return void
     */
    private function callDriver($phone, $soundId = 76)
    {
        Log::info("Call Driver -> " . $phone);
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