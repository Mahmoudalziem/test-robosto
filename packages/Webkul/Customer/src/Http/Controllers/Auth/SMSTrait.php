<?php

namespace Webkul\Customer\Http\Controllers\Auth;
;
use Illuminate\Support\Facades\Http;
use Webkul\Customer\Services\SMS\VictoryLink\CheckCredit;
use Webkul\Customer\Services\SMS\VictoryLink\SendSMS;

trait SMSTrait
{
    /**
     * Send SMS to the phone
     */
    public function sendSMS($phone, $text)
    {
        $lang = request()->header('lang') ?? 'ar';
        $sender = 'Robosto';

        $sms = new SendSMS($phone, $text, $lang, $sender);
        
        return $sms->send();
    }
    
    
    /**
     * Send SMS to the phone
     */
    public function checkCredit()
    {
        return (new CheckCredit())->checkCredit();
    }
}
