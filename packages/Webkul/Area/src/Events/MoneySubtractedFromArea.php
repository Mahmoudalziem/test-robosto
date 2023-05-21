<?php

namespace Webkul\Area\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneySubtractedFromArea extends ShouldBeStored
{
    /** @var int */
    public $areaId;

    /** @var float */
    public $amount;

    /** @var int */
    public $areaManagerId;
    
    /** @var int */
    public $accounatntId;

    public function __construct(int $areaId, float $amount, int $areaManagerId = null, int $accounatntId = null)
    {
        $this->areaId = $areaId;

        $this->amount = $amount;

        $this->areaManagerId = $areaManagerId;
        
        $this->accounatntId = $accounatntId;
    }
}
