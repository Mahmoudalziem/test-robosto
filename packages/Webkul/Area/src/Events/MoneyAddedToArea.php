<?php

namespace Webkul\Area\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneyAddedToArea extends ShouldBeStored
{
    /** @var int */
    public $areaId;

    /** @var float */
    public $amount;

    /** @var int */
    public $driverId;

    /** @var int */
    public $areaManagerId;

    public function __construct(int $areaId, float $amount, int $driverId = null, int $areaManagerId = null)
    {
        $this->areaId = $areaId;

        $this->amount = $amount;

        $this->driverId = $driverId;

        $this->areaManagerId = $areaManagerId;
    }
}
