<?php
namespace Webkul\Customer\Services\SMS\VictoryLink;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;

class VictoryLinkConfig
{

    /**
     * @var string
     */
    private $username = '';
    
    /**
     * @var string
     */
    private $password = '';
    
    /**
     * @var string
     */
    private $url = 'https://smsvas.vlserv.com/KannelSending/service.asmx';

    public function __construct()
    {
        $this->setUsername();
        $this->setPassword();
    }

    /**
     * SET Username from Config
     *
     * @return string
     */
    private function setUsername()
    {
        $this->username = config('robosto.VICTORY_LINK_USER');
    }
    
    /**
     * SET Username from Config
     *
     * @return string
     */
    private function setPassword()
    {
        $this->password = config('robosto.VICTORY_LINK_PASSWORD');
    }
    
    /**
     * Get Username from Config
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * Get Username from Config
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    protected function sendSMSWithDlrURL()
    {
        return $this->url . '/SendSMSWithDLR';
    }
    
    protected function checkCreditURL()
    {
        return $this->url . '/CheckCredit';
    }
    
    protected function getURL()
    {
        return $this->url;
    }

    /**
     * Handle Errors
     */
    public function handleResponseError(int $responseCode, string $receiver)
    {
        $action = '';

        if ($responseCode == 0) {
            // Sent Successfully
            Log::info('SMS Sent Successfully');
            return true;
        } elseif ($responseCode == -1) {
            // User is not subscribed
            $action = 'Username inCorrect';
        } elseif ($responseCode == -1) {
            // User is not subscribed
            $action = 'Username inCorrect';
        } elseif ($responseCode == -5) {
            // out of credit.
            $action = 'Out of credit.';
        } elseif ($responseCode == -11) {
            // Invalid language.
            $action = 'Invalid language';
        } elseif ($responseCode == -12) {
            // SMS is empty.
            $action = 'SMS is empty for Phone ' . $receiver;
        } elseif ($responseCode == -13) {
            // Invalid Sender Name
            $action = 'Invalid Sender Name';
        } elseif ($responseCode == -25) {
            // Sending rate greater than receiving rate (only for send/receive accounts).
            $action = 'Sending rate greater than receiving rate (only for send/receive accounts).';
        } elseif ($responseCode == -100) {
            // Other Error Happened
            $action = 'Error happens when Send OTP for Phone ' . $receiver;
        }

        Event::dispatch('customer.send-otp', $action);

        Log::info($action);

        return true;
    }
}