<?php

namespace Webkul\Area\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AreaManagerPendingMoneyReceived extends ShouldBeStored
{
    /** @var int */
    public $areaManagerId;
    
    /** @var float */
    public $amount;
    
    /** @var int */
    public $accountantId;

    public function __construct(int $areaManagerId, float $amount, int $accountantId = null)
    {
        $this->areaManagerId = $areaManagerId;

        $this->amount = $amount;
        
        $this->accountantId = $accountantId;
    }
}
