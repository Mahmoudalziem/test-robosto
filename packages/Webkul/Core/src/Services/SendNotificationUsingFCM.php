<?php
namespace Webkul\Core\Services;

use Illuminate\Support\Facades\Log;
use App\Exceptions\NotificationWithCurlException;


/**
 * Class used to send push notification to android and iOS for FireBase.
 * @author  Nagesh Badgujar
 */
class SendNotificationUsingFCM
{

    /**
     * represents the Firebase Server Key.
     */
    private $serverKey;

    /**
     * URL of firebase.
     */
    private $url = 'https://fcm.googleapis.com/fcm/send';


    public function __construct()
    {
        $this->serverKey = config('fcm.http.server_key');
        $this->limit = 1000;
        $this->setHeader();
    }

    /**
     * sets the limit for sending the multiple notifications batch.
     * @param type $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }


    /**
     * send notification to Android devices.
     * 
     * @param string|array $token
     * @param array $data
     * 
     * @return mixed
     */
    public function sendNotification($tokens, array $data = [])
    {
        $fields = array(
            'notification' => array(
                'title'     => $data['title'],
                'body'      => $data['body']
            ),
            'data' => isset($data['data']) ? $data['data'] : null,
        );
        if (is_array($tokens) && !empty($tokens)) {
                foreach ($tokens as $token) {
                    $fields['to'] = $token;
                    $this->curl($fields);
                }

        } else {
            $fields['to'] = $tokens;
            $this->curl($fields);

        }

        return true;
    }

    /**
     * sets the header for curl request.
     * @param type $key
     */
    public function setHeader()
    {
        $this->headers = array(
            'Authorization: key=' . $this->serverKey,
            'Content-Type: application/json'
        );
    }

    /**
     * send the curl request to FCM.
     * @param type $fields
     * @return type
     */
    private function curl($fields)
    {
        try {
            $result = requestWithCurl($this->url, 'POST', $fields, $this->headers);
            
        } catch (\Exception $th) {
            throw new NotificationWithCurlException(null, "There was an error while sending notification using curl -> " . $th->getMessage());
        }
        
        return $result;
    }
}
