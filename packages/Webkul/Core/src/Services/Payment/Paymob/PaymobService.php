<?php

namespace Webkul\Core\Services\Payment\Paymob;

use Webkul\Sales\Models\Order;
use App\Enums\TrackingUserEvents;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Event;
use Webkul\Customer\Models\PaymobCard;
use Webkul\Core\Services\Payment\Payment;
use Webkul\Customer\Models\CustomerPayment;
use Webkul\Customer\Models\PaymobPendingCard;
use Webkul\Core\Services\Payment\Paymob\PaymobAPI;
use Webkul\Sales\Http\Resources\OrderItemPaymentResource;

class PaymobService extends PaymobAPI {

    protected $customer;
    protected $apiKey;
    protected $integerationID;
    protected $authToken;
    protected $iframeID;
    protected $amount_base_rate;

    public function __construct($customer = null, $saveToken = true) {

        $this->apiKey = config('paymob.api_key');
        $authApiResponse = $this->authenticationRequest();

        if (isset($authApiResponse['response']['token']) && $authApiResponse['response']['token']) {
            $this->authToken = $authApiResponse['response']['token'];
        }

        $this->integerationID = $saveToken == true ? config('paymob.integeration_id') : config('paymob.integeration_moto_id');
        $this->amount_base_rate = config('paymob.amount_base_rate');

        $this->iframeID = config('paymob.iframe_id');
        if ($customer) {
            $this->customer = $customer;
        }
    }

    // Authentication Request

    public function authenticationRequest() {
        $postData = [
            "api_key" => $this->apiKey,
        ];
        $response = Http::post(config('paymob.url.token'), $postData);

        if ($response->successful()) {
            return $this->successHandlerAction($response);
        } else {
            $action = "Authentication Api Request Failed!";
            return $this->errorHandlerAction($response, $action);
        }
    }

// Order Registration API
    public function orderRegistrationAPI($amount_cents, $merchant_order_id) {
        $postData = [
            "auth_token" => $this->authToken,
            "delivery_needed" => false,
            "amount_cents" => $amount_cents,
            "currency" => "EGP",
            "merchant_order_id" => $merchant_order_id, //rand(1, 1000),
            "items" => []
        ];
        $response = Http::post(config('paymob.url.order'), $postData);
        $responseApi = $response->json();
        if ($response->successful()) {
            return $this->successHandlerAction($response);
        } else {

            if ($response->status() == 401) {
                $action = $responseApi['detail']; //
            } elseif ($response->status() == 422) {
                // Duplicate order id
                $action = 'Merchant order id dublicated!'; // 
            } else {
                $action = 'Order Registration API Failed!'; // 
            }
            return $this->errorHandlerAction($response, $action);
        }
    }

// Payment Key Request
    public function paymentKeyRequest($orderId, $amountCents) {

        $postData = [
            "auth_token" => $this->authToken,
            "order_id" => $orderId,
            "amount_cents" => $amountCents,
            'expiration' => 3600,
            "billing_data" => [
                "apartment" => "N/A",
                "email" => $this->customer->email != "" ? $this->customer->email : "info@robosto.com",
                "floor" => "N/A",
                "first_name" => explode(" ", $this->customer->name, 2)[0],
                "street" => "N/A",
                "building" => "8028",
                "phone_number" => $this->customer->phone,
                "shipping_method" => "N/A",
                "postal_code" => "N/A",
                "city" => "N/A",
                "country" => "N/A",
                "last_name" => isset(explode(" ", $this->customer->name, 2)[1]) ? explode(" ", $this->customer->name, 2)[1] : $this->customer->name,
                "state" => "N/A",
            ],
            "currency" => 'EGP',
            "integration_id" => $this->integerationID
        ];

        $response = Http::post(config('paymob.url.payment_key'), $postData);
        $apiResponse = $response->json();

        if ($response->successful()) {
            return $this->successHandlerAction($response);
        } else {

            if ($response->status() == 404) {
                $action = 'Cannot retrieve order !'; // 
            } else {
                $action = 'Payment Key Request API Failed!'; // 
            }
            return $this->errorHandlerAction($response, $action);
        }
    }

// generate IFrame  
    public function generateIFrame() {
        // authentication Api Request check
        $authApiResponse = $this->authenticationRequest();

        if ($authApiResponse['status'] == false) {
            return $authApiResponse; // return error handler
        }

        $amountCents = $this->getAmountInCents(1);
        $merchant_order_id = "cust-" . $this->customer->id . "-" . uniqid();
        $paymobOrderResponse = $this->orderRegistrationAPI($amountCents, $merchant_order_id);

        if ($paymobOrderResponse['status']) {
            $paymobOrderId = $paymobOrderResponse['response']['id'] ?? null;
            $paymentTokenResponse = $this->paymentKeyRequest($paymobOrderId, $amountCents);

            if ($paymentTokenResponse['status']) {
                $paymentToken = $paymentTokenResponse['response']['token']; // token
                return $iFrame = config('paymob.url.iframe') . $this->iframeID . '?payment_token=' . $paymentToken;
            } else {
                return $paymentTokenResponse;
            }
        } else {
            return $paymobOrderResponse;
        }
    }

    public function addNewCard($data) {

        $customer_id = explode("-", $data['merchant_order_id'])[1];
        $paymobPendingCard = PaymobPendingCard::where('order_id', $data['order'])->first();
        $customer = Customer::find($customer_id);

        $checkOldCard = PaymobCard::where('customer_id', $customer->id)
                ->where('brand', $paymobPendingCard['brand'])
                ->where('last_four', $paymobPendingCard['last_four']);
        if ($checkOldCard->count() > 0) { // old card of customer exists
            $paymobCard = $checkOldCard->first();
            $checkOldCard->first()->update(
                    [
                        'token' => $paymobPendingCard['token'],
                        'order_id' => $paymobPendingCard['order_id'],
            ]);
        } else {
            $customer->paymobCards()->update(['is_default' => 0]);
            $paymobCard = $customer->paymobCards()->create([
                'last_four' => $paymobPendingCard['last_four'],
                'brand' => $paymobPendingCard['brand'],
                'token' => $paymobPendingCard['token'],
                'order_id' => $paymobPendingCard['order_id'],
                'email' => $paymobPendingCard['email'],
                'customer_id' => $customer->id,
            ]);

            Event::dispatch('tracking.user.event', [TrackingUserEvents::ADD_PAYMENT_INFO, $customer]);
        }

        if ($paymobCard) {
            $paymobPendingCard->delete();
            $amountCents = 100; //cents
            $this->customerPayment($amountCents, $data, null, $paymobCard);
        }
        return false;
    }

// chargeViaCardToken
    public function chargeViaCardToken($amountCents, $order, $selectedCard = null) {

        $amountCents = $this->getAmountInCents($amountCents);
        $merchant_order_id = "order-" . $order->id . "-" . uniqid();

        $paymobOrderResponse = $this->orderRegistrationAPI($amountCents, $merchant_order_id);

        if ($paymobOrderResponse['status']) {
            $paymobOrderId = $paymobOrderResponse['response']['id'] ?? null;
            $paymentTokenResponse = $this->paymentKeyRequest($paymobOrderId, $amountCents);

            if ($paymentTokenResponse['status']) {
                $paymentToken = $paymentTokenResponse['response']['token']; // token
                return $this->cardToken($amountCents, $paymentToken, $order, $selectedCard);
            } else {
                return $paymentTokenResponse;
            }
        } else {
            return $paymobOrderResponse;
        }
    }

    public function cardToken($amountCents, $paymentToken, $order, $selectedCard = null) {

        if (!$selectedCard) {
            // get default card
            $paymobCard = $this->customer->paymobCards()->where("is_default", 1)->first();
            $cardToken = $paymobCard['token'];
        } else {
            $paymobCard = $this->customer->paymobCards()->where("id", $selectedCard)->first();
            $cardToken = $paymobCard['token'];
        }
        Log::info(["integerationID :" => $this->integerationID]);

        $source['identifier'] = $cardToken;
        $source['subtype'] = 'TOKEN';
        $cardData = [
            'source' => $source,
            "payment_token" => $paymentToken,
        ];

        $response = Http::post(config('paymob.url.card_token'), $cardData);
        $responseApi = $response->json();
        $this->customerPayment($amountCents, $responseApi, $order, $paymobCard);

        if ($response->successful()) {
            if ($responseApi["success"] == "true") {
                return $this->successHandlerAction($response);
            } else {
                return $this->errorHandlerAction($response, $response['data.message']);
            }
        } else {

            if ($response->status() == 401) {
                $action = $responseApi['detail'];
                $action = 'Invalid payment token or expired signature!'; //   
            } elseif ($response->status() == 404) {
                $action = $responseApi['message']; //404 CardToken matching query does not exist.
                $action = 'CardToken matching query does not exist!'; //   
            } else {
                
            }

            return $this->errorHandlerAction($response, $action);
        }
    }

// customer payment history
    private function customerPayment($amountCents, $payload, $order, $selectedCard) {
        if ($order) {
            $custoemr_id = $order->customer_id;
        } else {
            $custoemr_id = $selectedCard->customer_id;
        }

        return CustomerPayment::create([
                    'customer_id' => $custoemr_id,
                    'order_id' => $order ? $order->id : null,
                    'paymob_card_id' => $selectedCard->id,
                    'paymob_order_id' => $payload['order'] ?? null,
                    'paymob_transaction_id' => $payload['id'] ?? null,
                    'amount' => number_format($amountCents / 100, 2),
                    'payload_response' => $payload,
                    'is_paid' => isset($payload['success']) ? ($payload['success'] == "true" ? 1 : 0) : 0,
        ]);
    }

// Payment savePendingToken
    public function savePendingToken($postData) {
        $dataObj = $postData['obj'];
        return PaymobPendingCard::create([
                    'last_four' => $dataObj['masked_pan'],
                    'brand' => $dataObj['card_subtype'],
                    'token' => $dataObj['token'],
                    'order_id' => $dataObj['order_id'],
                    'email' => $dataObj['email'],
        ]);
    }

    // Payment update PendingToken 
    // to save correct masked_pan
    // ex : 498765xxxxxx8769
    public function updatePendingToken($postData) {
        $dataObj = $postData['obj'];
        if (isset($dataObj['order'])) {
            $pending = PaymobPendingCard::where('order_id', $dataObj['order']['id'])->first();
            if ($pending && isset($dataObj['data']['card_num'])) {
                $pending['last_four'] = $dataObj['data']['card_num'];
                return $pending->save();
            }
        }
    }

    protected function getAmountInCents($amount) {
        return number_format(( (float) $amount) * $this->amount_base_rate, 0, "", "");
    }

    public function customerTransactionInfo(string $transactionId) {
        $transaction = Http::get(config('paymob.url.transaction' . $transactionId), ["token" => $this->authToken]);
        return $transaction;
    }

    public function paymobOrderDetails($paymobOrderId, $merchant_order_id) {
        $postData = [
            "auth_token" => $this->authToken,
            "merchant_order_id" => $merchant_order_id,
            "order_id" => $paymobOrderId
        ];

        $response = Http::post(config('paymob.url.inquiry_order'), $postData);

        return $apiResponse = json_decode($response->getBody());
    }

}
