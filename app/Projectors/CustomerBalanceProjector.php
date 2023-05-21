<?php

namespace App\Projectors;

use Webkul\Customer\Models\Customer;
use Webkul\Customer\Events\MoneyAdded;
use Webkul\Customer\Events\MoneySubtracted;
use Webkul\Customer\Events\InvitationMoneyAdded;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Webkul\Customer\Events\BNPLMoneySubtracted;
use Webkul\Customer\Events\WalletMoneyAdded;
use Webkul\Customer\Events\WalletMoneySubtracted;
use Webkul\Customer\Events\MoneyAddedFromPromotionCashback;

class CustomerBalanceProjector extends Projector
{
    public function onMoneyAdded(MoneyAdded $event)
    {
        $customer = Customer::find($event->customerId);

        $customer->wallet += $event->amount;

        $customer->save();
    }
    
    
    public function onInvitationMoneyAdded(InvitationMoneyAdded $event)
    {
        $customer = Customer::find($event->customerId);

        $customer->wallet += $event->amount;

        $customer->save();
    }
    
    
    public function onWalletMoneyAdded(WalletMoneyAdded $event)
    {
        $customer = Customer::find($event->customerId);

        $customer->wallet += $event->amount;

        $customer->save();
    }

    public function onMoneySubtracted(MoneySubtracted $event)
    {
        $customer = Customer::find($event->customerId);

        $customer->wallet -= $event->amount;

        $customer->save();
    }
    
    public function onWalletMoneySubtracted(WalletMoneySubtracted $event)
    {
        $customer = Customer::find($event->customerId);

        $customer->wallet -= $event->amount;

        $customer->save();
    }
    
    public function onMoneyAddedFromPromotionCashback(MoneyAddedFromPromotionCashback $event)
    {
        $customer = Customer::find($event->customerId);

        $customer->wallet += $event->amount;

        $customer->save();
    }    
    public function onBNPLMoneySubtracted(BNPLMoneySubtracted $event)
    {
        $customer = Customer::find($event->customerId);

        $customer->wallet -= $event->amount;
        $customer->credit_wallet -= $event->amount;
        $customer->save();
    }
}
