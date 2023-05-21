<?php

namespace Webkul\Driver\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneyAdded extends ShouldBeStored
{
    /** @var int */
    public $driverId;

    /** @var float */
    public $amount;

    /** @var int */
    public $orderId;
    
    /** @var int */
    public $orderIncrementId;

    public function __construct(int $driverId, float $amount, int $orderId = null, int $orderIncrementId = null)
    {
        $this->driverId = $driverId;

        $this->amount = $amount;
        
        $this->orderId = $orderId;

        $this->orderIncrementId = $orderIncrementId;

        // $this->setMetaData(['order' =>  $order]);
    }
}