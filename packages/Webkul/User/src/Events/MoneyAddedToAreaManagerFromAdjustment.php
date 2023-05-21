<?php

namespace Webkul\User\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;


class MoneyAddedToAreaManagerFromAdjustment extends ShouldBeStored
{
    /** @var int */
    public $areaManagerId;

    /** @var float */
    public $amount;

    /** @var int */
    public $areaId;

    /** @var int */
    public $adjustmentId;


    public function __construct(int $areaManagerId, float $amount, int $areaId = null, int $adjustmentId = null)
    {
        $this->areaManagerId = $areaManagerId;
        
        $this->amount = $amount;
        
        $this->areaId = $areaId;
        
        $this->adjustmentId = $adjustmentId;
    }
}
