<?php

namespace Webkul\Area\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AreaManagerPendingMoney extends ShouldBeStored
{
    /** @var int */
    public $areaManagerId;
    
    /** @var float */
    public $amount;
    
    /** @var int */
    public $areaId;

    public function __construct(int $areaManagerId, float $amount, int $areaId = null)
    {
        $this->areaManagerId = $areaManagerId;

        $this->amount = $amount;
        
        $this->areaId = $areaId;
    }
}
