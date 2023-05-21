<?php
namespace Webkul\Core\Services\Payment\Vapulus;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Services\Payment\Payment;
use Webkul\Customer\Models\Customer;

class VapulusService extends VapulusAPI implements Payment
{

    private $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @param array $data
     * 
     * @return mixed
     */
    public function addNewCard(array $data)
    {
        $postData = array(
            'cardNum' =>  $data['card_number'],
            'cardExp' =>  $data['card_exp'],
            'cardCVC' =>  $data['card_cvc'],
            'holderName' => $data['card_name'],
            'mobileNumber' => $this->customer->phone,
            'email' => $this->customer->email ? $this->customer->email : config('robosto.PAYMENT_MAIL'),
        );

        return $this->addCard($postData);
    }

    /**
     * @param array $data
     * 
     * @return mixed
     */
    public function resendOtp(array $data)
    {
        return $this->resendOtp($data);
    }

    /**
     * @param array $data
     * 
     * @return mixed
     */
    public function validateOTP(array $data)
    {
        return $this->validateOTP($data);
    }

    /**
     * @param array $data
     * 
     * @return mixed
     */
    public function customerPaymentInfo(string $userId)
    {
        return $this->userInfo($userId);
    }

    /**
     * @param array $data
     * 
     * @return mixed
     */
    public function customerCardInfo(string $userId, string $cardId)
    {
        return $this->cardInfo($userId, $cardId);
    }

    /**
     * @param array $data
     * 
     * @return mixed
     */
    public function customerTransactionsList(array $data)
    {
        return $this->transactionsList($data);
    }

    /**
     * @param array $data
     * 
     * @return mixed
     */
    public function customerTransactionInfo(string $transactionId)
    {
        return $this->transactionInfo($transactionId);
    }
    
    
    /**
     * @param array $data
     * 
     * @return mixed
     */
    public function customerTransactionStatus(array $data)
    {
        return $this->transactionStatus($data);
    }

    
}