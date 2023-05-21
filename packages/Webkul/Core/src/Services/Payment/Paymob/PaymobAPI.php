<?php

namespace Webkul\Core\Services\Payment\Paymob;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;

class PaymobAPI {

    public function validateHmac($hmac, $trans_id) {
        $url = config('paymob.url.hmac');

        $response = Http::withToken($this->authToken)
                ->get("$url/$trans_id/hmac_calc");
        $responseApi = $response->json();

        //   Log::info(['validateHmac' => $response]);
        if ($response->status() == 404) {
            $action = $responseApi['detail']; //
            Event::dispatch('paymob.payment.error', $action);
            return [
                'status' => false,
                'code' => $response->status(),
                'reason' => $action
            ];
        }

        return $response['hmac'] == $hmac ? [
            'status' => true,
            'code' => $response->status(),
            'response' => "true hmac!",
                ] :
                ['status' => false,
            'code' => 405,
            'reason' => "invalid hmac!",
        ];
    }

    public function generateHMAC($params, $hmacResponse = null) {
        $array = $params;

        ksort($array);
        $arrayValues = array_values($array);

        // sort by Lexicographical order
        $arrayKeys = array_keys($array);

        // concatenated payload
        $concatenatedPayload = implode($arrayValues);

        // generate HMAC  
        $hmacCalc = hash_hmac('sha512', $concatenatedPayload, config('paymob.url.hmac'));

        return $hmacCalc;
    }

    protected function successHandlerAction(Response $response) {

        return [
            'status' => true,
            'code' => $response->status(),
            'response' => $response->json()
        ];
    }

    protected function errorHandlerAction(Response $response, $action, $serverError = null) {

        Event::dispatch('paymob.payment.error', $action);

        if ($response->serverError()) {
            $action = "Paymob server error!";
        }
        return [
            'status' => false,
            'code' => $response->status(),
            'reason' => $action
        ];
    }

}
