<?php

namespace Webkul\Driver\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneySubtracted extends ShouldBeStored
{
    /** @var int */
    public $driverId;

    /** @var float */
    public $amount;

    public function __construct(int $driverId, float $amount)
    {
        $this->driverId = $driverId;

        $this->amount = $amount;
    }
}
