<?php

namespace App\Projectors;

use Webkul\User\Models\Admin;
use Webkul\User\Events\MoneyAddedToAccountant;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Webkul\User\Events\MoneySubtractedFromAccountant;

class AcoountantBalanceProjector extends Projector
{
    public function onMoneyAdded(MoneyAddedToAccountant $event)
    {
        $accountant = Admin::find($event->accountantId);

        $wallet = $accountant->accountantWallet;

        if (!$wallet) {
            $wallet = $accountant->accountantWallet()->create();
        }

        $wallet->wallet += $event->amount;

        $wallet->total_wallet += $event->amount;

        $wallet->save();
    }

    public function onMoneySubtractedFromAccountant(MoneySubtractedFromAccountant $event)
    {
        $accountant = Admin::find($event->accountantId);
        $wallet = $accountant->accountantWallet;

        $wallet->wallet -= $event->amount;

        $wallet->save();
    }
}
