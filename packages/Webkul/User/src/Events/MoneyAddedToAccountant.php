<?php

namespace Webkul\User\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneyAddedToAccountant extends ShouldBeStored
{
    /** @var int */
    public $accountantId;

    /** @var float */
    public $amount;

    /** @var int */
    public $areaId;

    /** @var int */
    public $areaManagerId;


    public function __construct(int $accountantId, float $amount, int $areaId = null, int $areaManagerId = null)
    {
        $this->accountantId = $accountantId;

        $this->amount = $amount;

        $this->areaId = $areaId;

        $this->areaManagerId = $areaManagerId;
    }
}
