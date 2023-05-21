<?php

namespace Webkul\Customer\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneyAddedFromPromotionCashback extends ShouldBeStored
{
    /** @var int */
    public $customerId;

    /** @var float */
    public $amount;

    /** @var int */
    public $orderId;
    
    /** @var int */
    public $promotionId;

    public function __construct(int $customerId, float $amount, int $orderId = null, int $promotionId = null)
    {
        $this->customerId = $customerId;

        $this->amount = $amount;
        
        $this->orderId = $orderId;

        $this->promotionId = $promotionId;

        // $this->setMetaData(['order' =>  $order]);
    }
}
