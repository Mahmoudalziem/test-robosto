<?php

namespace Webkul\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider {

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        Event::listen('admin.log.activity', 'Webkul\Admin\Listeners\ActivityLog@activityLog');

        Event::listen('admin.alert.alertType', 'Webkul\Admin\Listeners\AlertNotify@alertType');


        Event::listen('admin.alert.driver_sign_in', 'Webkul\Admin\Listeners\AlertNotify@driver_sign_in');
        Event::listen('admin.alert.driver_sign_out', 'Webkul\Admin\Listeners\AlertNotify@driver_sign_out');
        Event::listen('admin.alert.driver_request_break', 'Webkul\Admin\Listeners\AlertNotify@driver_request_break');
        Event::listen('admin.alert.driver_cancelled_order', 'Webkul\Admin\Listeners\AlertNotify@driver_cancelled_order');

        Event::listen('admin.alert.collector_sign_in', 'Webkul\Admin\Listeners\AlertNotify@collector_sign_in');
        Event::listen('admin.alert.collector_sign_out', 'Webkul\Admin\Listeners\AlertNotify@collector_sign_out');
        
        Event::listen('admin.alert.admin_create_purchase_order', 'Webkul\Admin\Listeners\AlertNotify@admin_create_purchase_order');
        Event::listen('admin.alert.admin_create_transfer_order', 'Webkul\Admin\Listeners\AlertNotify@admin_create_transfer_order');
        Event::listen('admin.alert.admin_create_adjustment_order', 'Webkul\Admin\Listeners\AlertNotify@admin_create_adjustment_order');
        
        Event::listen('admin.alert.admin_cancelled_order', 'Webkul\Admin\Listeners\AlertNotify@admin_cancelled_order');
    }

}
