<?php

namespace Webkul\Customer\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneySubtracted extends ShouldBeStored
{
    /** @var int */
    public $customerId;

    /** @var float */
    public $amount;

    /** @var int */
    public $orderId;

    /** @var int */
    public $orderIncrementId;

    public function __construct(int $customerId, float $amount, int $orderId = null, int $orderIncrementId = null)
    {
        $this->customerId = $customerId;

        $this->amount = $amount;
        
        $this->orderId = $orderId;

        $this->orderIncrementId = $orderIncrementId;

        // $this->setMetaData(['order' =>  $order]);
    }
}
