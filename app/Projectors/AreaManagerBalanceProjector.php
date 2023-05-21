<?php

namespace App\Projectors;

use Webkul\User\Models\Admin;
use Webkul\Area\Events\AreaManagerPendingMoney;
use Webkul\User\Events\MoneyAddedToAreaManager;
use Webkul\User\Events\MoneySubtractedFromAreaManager;
use Webkul\Area\Events\AreaManagerPendingMoneyReceived;
use Webkul\Area\Events\AreaManagerPendingMoneyCancelled;
use Webkul\User\Events\MoneyAddedToAreaManagerFromAdjustment;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class AreaManagerBalanceProjector extends Projector
{
    public function onMoneyAddedToAreaManager(MoneyAddedToAreaManager $event)
    {
        $areaManager = Admin::find($event->areaManagerId);

        $wallet = $areaManager->areaManagerWallet;

        if (!$wallet) {
            $wallet = $areaManager->areaManagerWallet()->create();
        }

        $wallet->wallet += $event->amount;

        $wallet->total_wallet += $event->amount;

        $wallet->save();

    }

    public function onMoneySubtractedFromAreaManager(MoneySubtractedFromAreaManager $event)
    {
        $areaManager = Admin::find($event->areaManagerId);
        $wallet = $areaManager->areaManagerWallet;

        $wallet->wallet -= $event->amount;

        $wallet->save();
    }

    public function onAreaManagerPendingMoney(AreaManagerPendingMoney $event)
    {
        $areaManager = Admin::find($event->areaManagerId);
        $wallet = $areaManager->areaManagerWallet;

        $wallet->wallet -= $event->amount;

        $wallet->pending_wallet += $event->amount;

        $wallet->save();
    }


    public function onAreaManagerPendingMoneyReceived(AreaManagerPendingMoneyReceived $event)
    {
        $areaManager = Admin::find($event->areaManagerId);
        $wallet = $areaManager->areaManagerWallet;
        
        $wallet->pending_wallet -= $event->amount;

        $wallet->save();
    }
    
    
    public function onAreaManagerPendingMoneyCancelled(AreaManagerPendingMoneyCancelled $event)
    {
        $areaManager = Admin::find($event->areaManagerId);
        $wallet = $areaManager->areaManagerWallet;

        $wallet->wallet += $event->amount;

        $wallet->pending_wallet -= $event->amount;

        $wallet->save();
    }
    
    public function onMoneyAddedToAreaManagerFromAdjustment(MoneyAddedToAreaManagerFromAdjustment $event)
    {
        $areaManager = Admin::find($event->areaManagerId);

        $wallet = $areaManager->areaManagerWallet;

        if (!$wallet) {
            $wallet = $areaManager->areaManagerWallet()->create();
        }

        $wallet->wallet += $event->amount;

        $wallet->total_wallet += $event->amount;

        $wallet->save();

    }    
}
