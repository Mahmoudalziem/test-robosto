<?php

namespace Webkul\Promotion\Services\PromotionValidation\Rules;

use Illuminate\Support\Facades\Log;
use Webkul\Promotion\Models\Promotion;
use Webkul\Promotion\Services\PromotionValidation\PromotionRule;

class MaxItemQtyAllowed extends PromotionRule {

    /**
     * @var array
     */
    private $items;

    /**
     * MinimumOrderRequirements constructor.
     * @param float $totalOrderPrice
     * @param int $totalItemsQuantity
     */
    public function __construct(array $items) {
        $this->items = $items;
    }

    /**
     * Check Promotion Valid from date to date
     * @param Promotion $promotion
     * @return bool
     */
    public function check(Promotion $promotion): bool {

        
        if (!is_null($promotion->max_item_qty)) {

            foreach ($this->items as $item) {
                if ($item['qty'] > $promotion->max_item_qty) {
                    Log::info('Order Item Count > max Item qty ');
                    return false;
                }
            }
        }

        return parent::check($promotion);
    }

}
