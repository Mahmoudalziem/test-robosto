<?php

namespace Webkul\User\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneySubtractedFromAccountant extends ShouldBeStored
{
    /** @var int */
    public $accounatntId;

    /** @var float */
    public $amount;

    public function __construct(int $accounatntId, float $amount)
    {
        $this->accounatntId = $accounatntId;

        $this->amount = $amount;
    }
}