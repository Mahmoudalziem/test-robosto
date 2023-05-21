<?php

namespace Webkul\User\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;


class MoneyAddedToAreaManager extends ShouldBeStored
{
    /** @var int */
    public $areaManagerId;

    /** @var float */
    public $amount;

    /** @var int */
    public $areaId;

    /** @var int */
    public $driverId;


    public function __construct(int $areaManagerId, float $amount, int $areaId = null, int $driverId = null)
    {
        $this->areaManagerId = $areaManagerId;
        
        $this->amount = $amount;
        
        $this->areaId = $areaId;
        
        $this->driverId = $driverId;
    }
}
