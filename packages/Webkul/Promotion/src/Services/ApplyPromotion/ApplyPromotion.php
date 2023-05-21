<?php
namespace Webkul\Promotion\Services\ApplyPromotion;

use Webkul\Promotion\Models\Promotion;

class ApplyPromotion
{
    /**
     * @var array
     */
    protected $items;
    
    /**
     * @var Promotion
     */
    protected $promotion;

    /**
     * Apply Constructor
     */
    public function __construct(Promotion $promotion, array $items)
    {
        $this->promotion = $promotion;
        $this->items = $items;
    }

    public function apply()
    {
        // Get Promotion Type 
        $promotionType = $this->promotion->apply->apply_type;

        if (!$promotionType) {
          return false;  
        }

        $apply = new Apply($this->promotion, $promotionType, $this->items);
        
        return $apply->apply();
    }

}