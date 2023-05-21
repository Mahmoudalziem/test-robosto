<?php
namespace Webkul\Promotion\Services\PromotionValidation\Rules;

use Illuminate\Support\Facades\Log;
use Webkul\Promotion\Models\Promotion;
use Webkul\Promotion\Services\PromotionValidation\PromotionRule;

class MinimumOrderRequirements extends PromotionRule
{
    /**
     * @var float
     */
    private $totalOrderPrice;

    /**
     * @var int
     */
    private $totalItemsQuantity;

    /**
     * MinimumOrderRequirements constructor.
     * @param float $totalOrderPrice
     * @param int $totalItemsQuantity
     */
    public function __construct(float $totalOrderPrice, int $totalItemsQuantity)
    {
        $this->totalOrderPrice = $totalOrderPrice;
        $this->totalItemsQuantity = $totalItemsQuantity;
    }

    /**
     * Check Promotion Valid from date to date
     * @param Promotion $promotion
     * @return bool
     */
    public function check(Promotion $promotion): bool
    {
        // Check Minimum Order Requirments based on promotion type [ total_price | total_qty ]
        if (!is_null($promotion->minimum_order_amount)) {
            if ($this->totalOrderPrice < $promotion->minimum_order_amount) {
                Log::info('Not Valid For Minimum Price');
                return false;
            }
        }

        if (!is_null($promotion->minimum_items_quantity)) {

            if ($this->totalItemsQuantity < $promotion->minimum_items_quantity) {
                Log::info('Not Valid For Qty');
                return false;
            }
        }

        return parent::check($promotion);
    }
    
}