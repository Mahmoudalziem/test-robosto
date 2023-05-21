<?php

namespace Webkul\Shipping\Providers;

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
            'shippment.log',
        ], 'Webkul\Shipping\Listeners\ShippmentLogs@addShippmentLog');
    }
}