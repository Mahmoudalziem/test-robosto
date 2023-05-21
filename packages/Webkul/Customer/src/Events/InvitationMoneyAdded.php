<?php

namespace Webkul\Customer\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class InvitationMoneyAdded extends ShouldBeStored
{
    /** @var int */
    public $customerId;

    /** @var float */
    public $amount;
    
    /** @var int */
    public $inviterId;


    /**
     * @param int $customerId
     * @param int $amount
     * @param int $inviterId
     */
    public function __construct(int $customerId, float $amount, int $inviterId)
    {
        $this->customerId = $customerId;
        
        $this->amount = $amount;
        
        $this->inviterId = $inviterId;
    }
}
