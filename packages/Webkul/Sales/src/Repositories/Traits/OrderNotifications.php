<?php
namespace Webkul\Sales\Repositories\Traits;

use Webkul\Driver\Models\Driver;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Event;
use Webkul\Collector\Models\Collector;
use Webkul\Sales\Models\Order as OrderModel;
use Webkul\Core\Services\SendPushNotification;
use Webkul\Core\Services\SendNotificationUsingFCM;

/**
 * Send Notifications to Drivers, Collectors, Customers
 */
trait OrderNotifications
{
    /**
     * @param OrderModel $order
     * @param array $data
     * @return bool
     * @throws InvalidOptionsException
     */
    public function sendNotificationToCustomer(OrderModel $order, array $data)
    {
        if(!$order->customer_id){
            return;
        }
        logOrderActionsInCache($order->id, 'start_send_notification_to_customer');
        Event::dispatch('app.order.send_notification_to_customer', $order);

        // Send Notification
        $customer = Customer::findOrFail($order->customer_id);
        $tokens = $customer->deviceToken->pluck('token')->toArray();
        $data = [
            'title' => $data['title'],
            'body' => $data['body'],
            'data' => isset($data['details']) ? $data['details'] : null
        ];

        logOrderActionsInCache($order->id, 'notification_to_customer_now');

        return (new SendNotificationUsingFCM())->sendNotification($tokens, $data);
    }


    /**
     * @param $driverId
     * @param OrderModel $order
     * @param array $data
     * @return bool
     * @throws InvalidOptionsException
     */
    public function sendNotificationToDriver($driverId, OrderModel $order, array $data)
    {
        Log::info("Send Notification to Driver -> " . $driverId);
        logOrderActionsInCache($order->id, 'start_send_notification_to_driver');

        Event::dispatch('app.order.send_notification_to_driver', $order);

        // Send Notification
        $driver = Driver::findOrFail($driverId);
        $tokens = $driver->deviceToken->pluck('token')->toArray();

        logOrderActionsInCache($order->id, 'notification_to_driver_now');

        return (new SendNotificationUsingFCM())->sendNotification($tokens, $data);
    }


    /**
     * @param OrderModel $order
     * @param array $data
     * @return bool
     * @throws InvalidOptionsException
     */
    public function sendNotificationToCollector(OrderModel $order, array $data)
    {
        $orderCollector = Collector::with('deviceToken')->find($order->collector_id);
        $tokens = $orderCollector->deviceToken->pluck('token')->toArray();
        
        logOrderActionsInCache($order->id, 'start_send_notification_to_collector');
        Event::dispatch('app.order.send_notification_to_collector', $order);

        // Get All Collectors Tokens
        // $collectors = Collector::with('deviceToken')->where('warehouse_id', $order->warehouse_id)->where('availability', 'online')->get();        
        // $tokens = [];
        // foreach ($collectors as $collector) {
        //     $tokens = array_merge($tokens, $collector->deviceToken->pluck('token')->toArray());
        // }
        
        
        $data = [
            'title' => $data['title'],
            'body' => $data['body'],
            'data' => $data['details']
        ];
        logOrderActionsInCache($order->id, 'notification_to_collector_now');

        return (new SendNotificationUsingFCM())->sendNotification($tokens, $data);
    }
}
