<?php

namespace App\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Webkul\Driver\Events\MoneyAdded;
use Webkul\Driver\Events\MoneySubtracted;
use Webkul\Driver\Models\Driver;

class DriverBalanceProjector extends Projector
{
    public function onMoneyAdded(MoneyAdded $event)
    {
        $driver = Driver::find($event->driverId);

        $driver->wallet += $event->amount;
        
        $driver->total_wallet += $event->amount;

        $driver->save();
    }

    public function onMoneySubtracted(MoneySubtracted $event)
    {
        $driver = Driver::find($event->driverId);

        $driver->wallet -= $event->amount;

        $driver->save();
    }
}
