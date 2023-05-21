<?php

namespace Webkul\Area\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AreaManagerPendingMoneyCancelled extends ShouldBeStored
{
    /** @var int */
    public $areaManagerId;
    
    /** @var float */
    public $amount;
    
    public function __construct(int $areaManagerId, float $amount)
    {
        $this->areaManagerId = $areaManagerId;

        $this->amount = $amount;
    }
}
