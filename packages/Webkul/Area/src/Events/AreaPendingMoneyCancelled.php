<?php

namespace Webkul\Area\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AreaPendingMoneyCancelled extends ShouldBeStored
{
    /** @var int */
    public $areaId;

    /** @var float */
    public $amount;

    public function __construct(int $areaId, float $amount)
    {
        $this->areaId = $areaId;

        $this->amount = $amount;
    }
}
