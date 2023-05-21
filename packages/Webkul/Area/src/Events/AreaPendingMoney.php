<?php

namespace Webkul\Area\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AreaPendingMoney extends ShouldBeStored
{
    /** @var int */
    public $areaId;

    /** @var float */
    public $amount;

    /** @var int */
    public $areaManagerId;

    public function __construct(int $areaId, float $amount, int $areaManagerId = null)
    {
        $this->areaId = $areaId;

        $this->amount = $amount;

        $this->areaManagerId = $areaManagerId;
    }
}
