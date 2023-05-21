<?php

namespace Webkul\Core\Services\Payment\Vapulus;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;


class VapulusAPI
{
    protected $base_url = 'https://api.vapulus.com:1338/';

    /**
     * Get APP_ID from Config
     *
     * @return string
     */
    private function getAppId()
    {
        return config('robosto.VAPULUS_APP_ID');
    }

    /**
     * Get APP_ID from Config
     *
     * @return string
     */
    private function getPassword()
    {
        return config('robosto.VAPULUS_PASSWORD');
    }

    /**
     * Get APP_ID from Config
     *
     * @return string
     */
    private function getHashSecret()
    {
        return config('robosto.VAPULUS_HASH_SECRET');
    }


    /**
     * @param mixed $hashSecret
     * @param array $postData
     * 
     * @return string
     */
    private function generateHash(array $postData)
    {
        $hashSecret = $this->getHashSecret();

        ksort($postData);

        $message = "";
        $appendAmp = 0;
        foreach ($postData as $key => $value) {
            if (strlen($value) > 0) {
                if ($appendAmp == 0) {
                    $message .= $key . '=' . $value;
                    $appendAmp = 1;
                } else {
                    $message .= '&' . $key . "=" . $value;
                }
            }
        }

        $secret = pack('H*', $hashSecret);

        return hash_hmac('sha256', $message, $secret);
    }



    /**
     * @param array $data
     * 
     * @return mixed
     */
    protected function addCard(array $data)
    {
        return $this->callApi('app/addCard', $data);
    }
    
    /**
     * @param array $data
     * 
     * @return mixed
     */
    protected function resendOtp(array $data)
    {
        return $this->callApi('app/resendCode', $data);
    }
    
    /**
     * @param array $data
     * 
     * @return mixed
     */
    protected function validateOTP(array $data)
    {
        return $this->callApi('app/validateOTP', $data);
    }
    
    
    /**
     * Just for Make Payment through registred user in our system with Card Number
     * 
     * @param array $data
     * 
     * @return mixed
     */
    protected function makePayment(array $data)
    {
        return $this->callApi('app/makePayment', $data);
    }


    /**
     * * Just for Make Payment for New User and Register him in our system with Card Number
     * 
     * @param array $data
     * 
     * @return mixed
     */
    protected function makeTransaction(array $data)
    {
        $data['onAccept']   = route('payment.success');
        $data['onFail']     = route('payment.fail');

        return $this->callApi('app/makeTransaction', $data);
    }

    /**
     * @param array $data
     * 
     * @return mixed
     */
    protected function userInfo(string $userId)
    {
        $data['userId'] = $userId;

        return $this->callApi('app/userInfo', $data);
    }


    /**
     * @param array $data
     * 
     * @return mixed
     */
    protected function cardInfo(string $userId, string $cardId)
    {
        $data['userId'] = $userId;
        $data['cardId'] = $cardId;

        return $this->callApi('app/cardInfo', $data);
    }

    /**
     * 
     * @param array $data
     * 
     * @return mixed
     */
    protected function transactionsList(array $data)
    {
        return $this->callApi('app/transactions/list', $data);
    }

    /**
     * @param array $data
     * 
     * @return mixed
     */
    protected function transactionInfo(string $transactionId)
    {
        $data['transactionId'] = $transactionId;

        return $this->callApi('app/transactionInfo', $data);
    }

    /**
     * 
     * @param array $data
     * 
     * @return mixed
     */
    protected function transactionStatus(array $data)
    {
        return $this->callApi('app/transaction/status', $data);
    }
    
    
    /**
     * @param string $apiName
     * @param array $data
     * 
     * @return mixed
     */
    private function handleResponse(Response $response)
    {
        // convert response from JSON to Array
        $apiResponse = $response->json();
        // Get Status Code
        $statusCode = $apiResponse['statusCode'];

        if ($statusCode == 200) {
            return [
                'status'    =>  true,
                'response'  =>  $apiResponse
            ];

        } elseif ($statusCode == 201) {
            $action = 'Invaild AppId or Password, check msg object for more information';

        } elseif ($statusCode == 202) {
            $action = 'App is not active, check msg object for more information';

        } elseif ($statusCode == 203) {
            $action = 'Invalid secureHash, check msg object for more information';

        } else {
            $action = 'Other Error';
        }

        Event::dispatch('vapulus.payment.error',$action);

        Log::info($action);

        return [
            'status'    =>  false,
            'reason'  =>  $action
        ];
    }
    
    /**
     * @param string $apiName
     * @param array $data
     * 
     * @return mixed
     */
    private function callApi(string $apiName, array $data)
    {
        // Prepare Data
        $postData = $data;
        $postData['hashSecret'] = $this->generateHash($postData);
        $postData['appId'] = $this->getAppId();
        $postData['password'] = $this->getPassword();

        // Call API
        $response = Http::post($this->base_url . $apiName, $postData);

        return $this->handleResponse($response);
    }
}
