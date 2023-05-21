<?php

namespace Webkul\Driver\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Webkul\Driver\Listeners\DriverCreateAfter;
use Webkul\Driver\Listeners\DriverCreateBefore;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register any events for your application.
     *
     * @return void
     *
     */
    public function boot()
    {
        Event::listen('driver.new-order-assigned', 'Webkul\Driver\Listeners\DriverWorkingCycle@newOrderAssigned');

        Event::listen('driver.start-delivery', 'Webkul\Driver\Listeners\DriverWorkingCycle@startDelivery');

        Event::listen('driver.order-delivered', 'Webkul\Driver\Listeners\DriverWorkingCycle@orderDelivered');
        
        Event::listen('driver.order-cancelled', 'Webkul\Driver\Listeners\DriverWorkingCycle@orderCancelled');
        Event::listen('test.list', 'Webkul\Driver\Listeners\DriverWorkingCycle@testListner');

        Event::listen('driver.order-delivered-bonus', 'Webkul\Driver\Listeners\DriverBonus@calculateNumberOfOrders');
        Event::listen('driver.working-hours-bonus', 'Webkul\Driver\Listeners\DriverBonus@calculateNumberOfWorkingHours');
        Event::listen('driver.customer-rating-bonus', 'Webkul\Driver\Listeners\DriverBonus@calculateCustomersRating');
        Event::listen('driver.supervisor-rating-bonus', 'Webkul\Driver\Listeners\DriverBonus@calculateSupervisorRating');
        Event::listen('driver.on-the-way-back-bonus', 'Webkul\Driver\Listeners\DriverBonus@calculateBackBonus');
        Event::listen('driver.working-path-bonus', 'Webkul\Driver\Listeners\DriverBonus@calculateWorkingPathRating');

        Event::listen('driver.create.before', "Webkul\Driver\Listeners\DriverCreateAfter@handle" );
        Event::listen('driver.create.after', "Webkul\Driver\Listeners\DriverCreateBefore@handle");
    }
}