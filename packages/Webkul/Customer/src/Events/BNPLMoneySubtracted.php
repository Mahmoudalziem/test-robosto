<?php

namespace Webkul\Customer\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class BNPLMoneySubtracted extends ShouldBeStored
{
    /** @var int */
    public $customerId;

    /** @var float */
    public $amount;

    /** @var int */
    public $orderId;

    public function __construct(int $customerId, float $amount, int $orderId = null)
    {
        $this->customerId = $customerId;

        $this->amount = $amount;
        
        $this->orderId = $orderId;
    }
}
