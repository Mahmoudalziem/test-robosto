<?php


namespace Webkul\Promotion\Services\PromotionValidation\Rules;


use Illuminate\Support\Facades\Log;
use Webkul\Promotion\Models\Promotion;
use Webkul\Promotion\Services\PromotionValidation\PromotionRule;

class Available extends PromotionRule
{
    /**
     * Check Promotion Valid from date to date
     * @param Promotion $promotion
     * @return bool
     */
    public function check(Promotion $promotion): bool
    {
        // Check Promotion Available
        if ($promotion->is_valid == 0 || $promotion->status == 0) {
            Log::info('Not Available');
            return false;
        }

        return parent::check($promotion);
    }
}