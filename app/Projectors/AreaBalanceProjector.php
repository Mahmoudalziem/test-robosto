<?php

namespace App\Projectors;

use Webkul\Area\Models\Area;
use Webkul\Area\Events\AreaPendingMoney;
use Webkul\Area\Events\MoneyAddedToArea;
use Webkul\Area\Events\MoneySubtractedFromArea;
use Webkul\Area\Events\AreaPendingMoneyReceived;
use Webkul\Area\Events\AreaPendingMoneyCancelled;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class AreaBalanceProjector extends Projector
{
    public function onMoneyAddedToArea(MoneyAddedToArea $event)
    {
        $area = Area::find($event->areaId);

        $area->wallet += $event->amount;

        $area->total_wallet += $event->amount;

        $area->save();
    }

    public function onMoneySubtractedFromArea(MoneySubtractedFromArea $event)
    {
        $area = Area::find($event->areaId);

        $area->wallet -= $event->amount;

        $area->save();
    }

    public function onAreaPendingMoney(AreaPendingMoney $event)
    {
        $area = Area::find($event->areaId);

        $area->wallet -= $event->amount;
        
        $area->pending_wallet += $event->amount;

        $area->save();
    }
    
    
    public function onAreaPendingMoneyReceived(AreaPendingMoneyReceived $event)
    {
        $area = Area::find($event->areaId);
        
        $area->pending_wallet -= $event->amount;

        $area->save();
    }
    
    
    public function onAreaPendingMoneyCancelled(AreaPendingMoneyCancelled $event)
    {
        $area = Area::find($event->areaId);

        $area->wallet += $event->amount;

        $area->pending_wallet -= $event->amount;

        $area->save();
    }
}
