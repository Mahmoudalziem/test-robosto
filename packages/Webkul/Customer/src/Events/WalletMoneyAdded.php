<?php

namespace Webkul\Customer\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class WalletMoneyAdded extends ShouldBeStored
{
    /** @var int */
    public $customerId;

    /** @var int */
    public $adminId;

    /** @var float */
    public $amount;

    /** @var string */
    public $note;
    

    public function __construct(int $customerId, int $adminId, float $amount, string $note)
    {
        $this->customerId = $customerId;
        
        $this->adminId = $adminId;

        $this->amount = $amount;
        
        $this->note = $note;

    }
}
