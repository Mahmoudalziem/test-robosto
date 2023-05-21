<?php

namespace Webkul\Sales\Listeners;

use Carbon\Carbon;
use Webkul\User\Models\Admin;
use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Core\Services\SendPushNotification;
use LaravelFCM\Message\Exceptions\InvalidOptionsException;

class OrderChanges implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param Order $order
     * @return void
     * @throws InvalidOptionsException
     */
    public function checkOrderFlagged(Order $order)
    {
        if (in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_WAITING_CUSTOMER_RESPONSE])) {
            $this->checkPendingFlagged($order);
        }

        if (in_array($order->status, [Order::STATUS_PREPARING, Order::STATUS_READY_TO_PICKUP, Order::STATUS_ON_THE_WAY, Order::STATUS_AT_PLACE])) {
            $this->checkActiveFlagged($order);
        }

        if (in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED, Order::STATUS_CANCELLED_FOR_ITEMS, Order::STATUS_RETURNED])) {
            $this->checkHistoryFlagged($order);
        }
    }

    /**
     * Handle the event.
     *
     * @param Order $order
     * @return void
     * @throws InvalidOptionsException
     */
    private function checkPendingFlagged(Order $order)
    {
        $pendingOrderTimeBuffer = config('robosto.PENDING_ORDER_BUFFER');

        $totalTime = Carbon::parse($order->created_at)->addMinutes($pendingOrderTimeBuffer)->timestamp;

        if (now()->timestamp > $totalTime) {
            Cache::put("order_{$order->id}_flagged", 1);
            // Send Notification To Admin
            $this->sendNotificationToAdmin();
        }
    }

    /**
     * Handle the event.
     *
     * @param Order $order
     * @return void
     * @throws InvalidOptionsException
     */
    private function checkActiveFlagged(Order $order)
    {
        // Get Prepairing Orders before this order included this order also
        $warehousePendingOrdersQtyShipped = Order::where('warehouse_id', $order->warehouse_id)
            ->where('status', Order::STATUS_PREPARING)->sum('items_qty_shipped');

        // Caclulate Preparinig Time
        $preparingTimeinSeconds = $warehousePendingOrdersQtyShipped * config('robosto.QAUNTITY_PREPARING_TIME');

        // from driver to customer within warehouse
        $deliveryTime = Cache::get("order_{$order->id}_delivery_time_in_seconds");

        $totalBufferingTimeInSeconds = $preparingTimeinSeconds + $deliveryTime;

        $totalTime = Carbon::parse($order->updated_at)->addSeconds($totalBufferingTimeInSeconds)->timestamp;

        if (now()->timestamp > $totalTime) {
            Cache::put("order_{$order->id}_flagged", 1);

            // Send Notification To Admin
            $this->sendNotificationToAdmin();
        }
    }

    /**
     * Handle the event.
     *
     * @param Order $order
     * @return void
     * @throws InvalidOptionsException
     */
    private function checkHistoryFlagged(Order $order)
    {
        if ($order->expected_on) {
            if ($order->expected_on->lt($order->delivered_at)) {

                Cache::put("order_{$order->id}_flagged", 1);
                // Send Notification To Admin
                $this->sendNotificationToAdmin();
            }
        }
    }

    /**
     * Handle the event.
     *
     * @return bool
     * @throws InvalidOptionsException
     */
    private function sendNotificationToAdmin()
    {
        return true;
    }
}
