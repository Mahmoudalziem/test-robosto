<?php

namespace Webkul\Customer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Webkul\Customer\Http\Requests\NewCardRequest;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Core\Services\Payment\Vapulus\VapulusService;
use Webkul\Customer\Http\Resources\Customer\CustomerCards;
use Webkul\Core\Services\Payment\Paymob\PaymobService;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Order;

class PaymentController extends BackendBaseController {

    /**
     * CustomerRepository object
     *
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * Create a new controller instance.
     *
     * @param CustomerRepository $customerRepository
     */
    public function __construct(CustomerRepository $customerRepository) {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @return JsonResponse
     */
    public function getCards() {

        $customer = auth('customer')->user();
        
        $cards = $customer->vapulusCards;

        return $this->responseSuccess(new CustomerCards($cards));
    }

    /**
     * @return JsonResponse
     */
    public function addCard(NewCardRequest $request) {
        $data = $request->only(['card_number', 'card_exp', 'card_cvc', 'card_name']);
        $customer = auth('customer')->user();

        // Call Vapulus Service        
        $pay = new VapulusService(auth('customer')->user());
        $newCard = $pay->addNewCard($data);

        // If Card addedd Successfully
        if ($newCard['status']) {
            $responseData = $newCard['response']['data'];
            $data['card_id'] = $responseData['cardId'];
            $data['user_id'] = $responseData['userId'];

            $this->customerRepository->createNewCard($customer, $data);

            return $this->responseSuccess();
        }

        return $this->responseError(422, $newCard['reason']);
    }

    /**
     * @return JsonResponse
     */
    public function authenticationRequest(Request $request) {
        $data = $request->all();
        $customer = auth('customer')->user();
        $customer = $this->customerRepository->find(27);

        // Call Fawry Service
        $pay = new PaymobService($customer);
        $response = $pay->authenticationRequest($data);

        return $this->responseSuccess($response);
    }

    public function orderRegistrationAPI(Request $request) {

        $data = $request->only(['auth_token', 'delivery_needed', 'amount_cents', 'currency']);
        $customer = auth('customer')->user();
        $customer = $this->customerRepository->find(27);

        // Call Fawry Service
        $pay = new PaymobService($customer);
        $response = $pay->orderRegistrationAPI($data);

        return $this->responseSuccess($response);
    }

    public function paymentKeyRequest(Request $request) {

        $data = $request->only(['auth_token', 'order_id', 'amount_cents', 'currency', 'integration_id']);
        $customer = auth('customer')->user();
        $customer = $this->customerRepository->find(27);

        // Call Fawry Service
        $pay = new PaymobService($customer);
        $response = $pay->paymentKeyRequest($data);

        return $this->responseSuccess($response);
    }

    public function generateIFrame(Request $request) {

        $customer = auth('customer')->user();
        if ($customer) {
            // Call Fawry Service
            $pay = new PaymobService($customer, true); // true for save card token
            $response = $pay->generateIFrame();
            return $this->responseSuccess($response);
        }

        return $this->responseError("you must be authorized!");
    }

    public function transactionProcessedCallback(Request $request) {
        $data = $request->all();
        $hmac = $request->hmac ?? null;

        $paymob = new PaymobService(null);
        if ($data['type'] == "TOKEN") {
            $cardData = $data['obj'];
            $paymob->savePendingToken($data);
        }
        if ($data['type'] == "TRANSACTION") {
            $postData = $data['obj'];
            $transctionId = $postData['id'];
            // check hmac
            $valid = $paymob->validateHmac($hmac, $transctionId);
            if ($postData['success'] && isset($postData['data']['card_num'])) {
                $paymob->updatePendingToken($data); // to save correct masked_pan
            } else {
                return $this->responseError();
            }
        }

        return $this->responseSuccess($data);
    }

    public function transactionResponseCallback(Request $request) {

        $data = $request->all();
        // Call Paymob Service
        $paymob = new PaymobService(null);

        Log::info(['Responsed Callback' => $data]);
        if ($data["success"] == "true") {
            $paymob->addNewCard($data);
            return $this->success();
            //  return $this->responseSuccess("Thank you have added your Credit Card Successfully!");
        }
        return $this->fail($data);
        //return $this->responseError(422, $data["data_message"]);
    }

    public function success() {
        return view('core::payment.success');
        //return "Thank you have added your Credit Card Successfully!";
    }

    public function fail($data) {
        return view('core::payment.fail', $data);
        return $data;
    }

    public function listCards(Request $request) {

        $customer = auth('customer')->user();
        $cards = $customer->paymobCards()->active()->get();
        
        $cards = new CustomerCards($cards);
        return $this->responseSuccess($cards);
    }

    public function deleteCard(Request $request) {
        $data = $request->only(['token']);
        $customer = auth('customer')->user();
        $customer = $customer->delete();

        return $this->responseSuccess($tokenResponse);
    }

    public function chargeViaCardToken(Request $request) {

        $data = $request->only(['amount_cents']);
        $customer = auth('customer')->user();

        // Call Fawry Service
        $order = Order::find(400);
        $paymob = new PaymobService($customer, false); // false for pay with token
        $response = $paymob->chargeViaCardToken($data['amount_cents'], $order);

        if (!$response['status']) {
            return $this->responseError($response['code'], $response['reason']);
        }

        return $this->responseSuccess("Thank you ,your order has been charged Successfully!");
    }

}
