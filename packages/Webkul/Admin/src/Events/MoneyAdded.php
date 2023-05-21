<?php

namespace Webkul\Admin\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneyAdded extends ShouldBeStored
{

    /** @var int */
    public $adminId;

    /** @var float */
    public $amount;
    
    /** @var string */
    public $role;

    public function __construct(int $adminId, float $amount, string $role = null)
    {
        $this->adminId = $adminId;

        $this->amount = $amount;
        
        $this->role = $role;
    }
}
