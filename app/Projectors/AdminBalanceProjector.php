<?php

namespace App\Projectors;

use Webkul\Admin\Events\MoneyAdded;
use Webkul\Admin\Events\MoneySubtracted;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Webkul\User\Models\Admin;

class AdminBalanceProjector extends Projector
{
    public function onMoneyAdded(MoneyAdded $event)
    {
        $admin = Admin::find($event->adminId);

        $admin->wallet += $event->amount;

        $admin->total_wallet += $event->amount;

        $admin->save();
    }

    public function onMoneySubtracted(MoneySubtracted $event)
    {
        $admin = Admin::find($event->adminId);

        $admin->wallet -= $event->amount;

        $admin->save();
    }
}
