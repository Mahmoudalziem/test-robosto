<?php

namespace Webkul\Sales\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        Event::listen([
            'app.order.placed',
            'app.order.processing.start',
            'app.order.check_items_avaialable',
            'app.order.send_changes_to_customer',
            'app.order.customer_reponse_changes_reponse',
            'app.order.customer_accept_changes',
            'app.order.update_status',
            'app.order.update_items_changes',
            'app.order.update_inventory_warehouse',
            'app.order.update_inventory_arae',
            'app.order.drivers_dispatching',
            'app.order.get_avaialable_drivers',
            'app.order.get_drivers_sorted',
            'app.order.insert_avaialable_drivers_in_db',
            'app.order.ready_drivers_dispatching',
            'app.order.send_notification_to_driver',
            'app.order.send_notification_to_customer',
            'app.order.send_notification_to_collector',
            'app.order.new_order_driver_reponse',
            'app.order.new_order_driver_accepted',
            'app.order.driver_ready_to_pickup',
            'app.order.driver_at_place',
        ], 'Webkul\Sales\Listeners\OrderChanges@checkOrderFlagged');

        Event::listen([
            'order.actual_logs',
        ], 'Webkul\Sales\Listeners\OrderLogs@updateOrderLogs');

        Event::listen([
            'app.order.delivered',
        ], 'Webkul\Sales\Listeners\OrderAction@updateProductSoldCount');

        Event::listen([
            'app.order.status_changed',
        ], 'Webkul\Sales\Listeners\OrderAction@orderStatusChanged');
    }
}