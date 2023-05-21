<?php
namespace Webkul\Core\Services;

use Illuminate\Support\Facades\Log;
use LaravelFCM\Facades\FCM;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;

class SendPushNotification
{

    /**
     * @param array $token
     * @param array $data
     * @return bool
     * @throws InvalidOptionsException
     */
    public static function send(array $token, array $data = [])
    {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);
        $option = $optionBuilder->build();

        $notificationBuilder = new PayloadNotificationBuilder();
        $notificationBuilder->setTitle($data['title'])
                            ->setBody($data['body']);
        $notification = $notificationBuilder->build();

        if (isset($data['data'])) {
            $dataBuilder = new PayloadDataBuilder();
            $dataBuilder->addData($data['data']);
            $data = $dataBuilder->build();
        } else {
            $data = null;
        }

        // check that the tokens are exist
        if (empty($token)) {
            return false;
        }

        $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);
        
        return $downstreamResponse->numberFailure() == 0;
    }

}