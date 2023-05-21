<?php
namespace Webkul\Customer\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Customer\Services\TrackingUser\Tracking;

class TrackUserInApp implements ShouldQueue
{
    /**
     * @param string $eventName
     * @param Customer $customr
     * @param array|null $data
     * 
     * @return void
     */
    public function sendUserAction(string $eventName, Customer $customr, array $data = null)
    {
        if (!config('robosto.ENABLE_USER_TRACK')) {
            return false;
        }

        (new Tracking($eventName, $customr, $data))->send();
    }


    /**
     * @param string $eventName
     * @param Customer $customr
     * @param array|null $data
     * 
     * @return void
     */
    public function sendItemsAction(string $eventName, Customer $customr, array $data = null)
    {
        if (!config('robosto.ENABLE_USER_TRACK')) {
            return false;
        }
        
        if (isset($data['request_data']['items'])) {
            foreach ($data['request_data']['items'] as $item) {
                $data['item'] = $item;
                
                (new Tracking($eventName, $customr, $data))->send();
            }
        }
    }
}