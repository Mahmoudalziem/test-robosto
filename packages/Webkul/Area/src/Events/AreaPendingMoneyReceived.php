<?php

namespace Webkul\Area\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AreaPendingMoneyReceived extends ShouldBeStored
{
    /** @var int */
    public $areaId;

    /** @var float */
    public $amount;

    /** @var int */
    public $accountantId;

    public function __construct(int $areaId, float $amount, int $accountantId = null)
    {
        $this->areaId = $areaId;

        $this->amount = $amount;

        $this->accountantId = $accountantId;
    }
}
