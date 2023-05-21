<?php


namespace Webkul\Promotion\Services\PromotionValidation\Rules;


use Illuminate\Support\Facades\Log;
use Webkul\Promotion\Models\Promotion;
use Webkul\Promotion\Services\PromotionValidation\PromotionRule;

class ValidDate extends PromotionRule
{
    /**
     * Check Promotion Valid from date to date
     * @param Promotion $promotion
     * @return bool
     */
    public function check(Promotion $promotion): bool
    {
        // Check Promotion Validity
        if (!is_null($promotion->start_validity) && !is_null($promotion->end_validity)) {
            if ($promotion->start_validity >= now()->toDateTimeString() || $promotion->end_validity <= now()->toDateTimeString()) {
                Log::info('Not Valid Date');
                return false;
            }
        }

        return parent::check($promotion);
    }
}
