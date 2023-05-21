<?php
namespace Webkul\Customer\Services\SMS\VictoryLink;

use Illuminate\Support\Facades\Http;

class CheckCredit extends VictoryLinkConfig
{
    public function __construct()
    {
        parent::__construct();
    }

    public function callCheckCreditApi()
    {
        $data = [
            'Username'      => $this->getUsername(),
            'Password'      => $this->getPassword(),
        ];
        $url = $this->checkCreditURL();
        
        // Call API
        $response = Http::get($url, $data);
        
        $responseCode = json_decode(simplexml_load_string($response->getBody(),'SimpleXMLElement',LIBXML_NOCDATA), true);

        return $responseCode;
    }

    /**
     * Send SMS
     */
    public function checkCredit()
    {
        // Call API
        return $this->callCheckCreditApi();
    }
    
}