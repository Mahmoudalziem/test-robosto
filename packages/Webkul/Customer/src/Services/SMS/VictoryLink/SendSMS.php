<?php
namespace Webkul\Customer\Services\SMS\VictoryLink;

use Illuminate\Support\Facades\Event;
use Webkul\Customer\Services\SMS\SMS;

class SendSMS extends VictoryLinkConfig implements SMS
{
    /**
     * @var string
     */
    private $receiver;
    
    /**
     * @var string
     */
    private $text;

    /**
     * @var string
     */
    private $lang;
    
    /**
     * @var string
     */
    private $sender;

    public function __construct(string $receiver, string $text, string $lang, string $sender)
    {
        parent::__construct();

        $this->receiver = $receiver;
        $this->text = $text;
        $this->lang = $lang;
        $this->sender = $sender;
    }

    public function callSendSMSApi()
    {
        $lang = $this->lang == 'ar' ? 'A' : 'E';
        $data = [
            'text'  =>  $this->text,
            'lang'  =>  $lang,
            'sender'  =>  $this->sender,
            'receiver'  =>  $this->receiver,
        ];
        
        $text = urlencode($data['text']);
        $url = $this->sendSMSWithDlrURL();
        $queryParameters = "Username={$this->getUsername()}&Password={$this->getPassword()}&SMSText={$text}&SMSLang={$data['lang']}&SMSSender={$data['sender']}&SMSReceiver={$data['receiver']}";
        $fullUrl = $url . "?" . $queryParameters;

        // Call API Using CURL
        $result = requestWithCurl($fullUrl, 'GET', null, [], false);

        // Decode Response
        $responseCode = json_decode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA), true);

        // Handle Response Status
        $this->handleResponseError($responseCode, $this->receiver);

        return $responseCode;
    }

    /**
     * Send SMS
     */
    public function send()
    {
        // Call API
        return $this->callSendSMSApi();
    }
    
}