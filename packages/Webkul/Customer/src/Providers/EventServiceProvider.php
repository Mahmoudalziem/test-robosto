<?php

namespace Webkul\Customer\Providers;

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
        Event::listen('tracking.user.event', 'Webkul\Customer\Listeners\TrackUserInApp@sendUserAction');
        Event::listen('tracking.user.event.items', 'Webkul\Customer\Listeners\TrackUserInApp@sendItemsAction');
    }
}