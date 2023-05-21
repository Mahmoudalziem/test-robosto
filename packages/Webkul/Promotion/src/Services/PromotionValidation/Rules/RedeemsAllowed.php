<?php


namespace Webkul\Promotion\Services\PromotionValidation\Rules;


use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Webkul\Promotion\Models\Promotion;
use Webkul\Promotion\Services\PromotionValidation\PromotionRule;

class RedeemsAllowed extends PromotionRule
{
    /**
     * @var Customer
     */
    private $customer;

    /**
     * Tags constructor.
     * @param Customer $customer
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Check Promotion Valid from date to date
     * @param Promotion $promotion
     * @return bool
     */
    public function check(Promotion $promotion): bool
    {
        // Check Customer doesn't Exceed Redeems Allowed
        $customerRedeems = $this->customer->promotionRedeems;

        if ($customerRedeems && !is_null($promotion->total_redeems_allowed) && $promotion->total_redeems_allowed != 0) {

            $customerRedeems = $customerRedeems->where('promotion_id', $promotion->id)->first();

            if ($customerRedeems && $customerRedeems->redeems_count >= $promotion->total_redeems_allowed) {
                Log::info('Not Valid For Redeems');
                return false;
            }
        }

        return parent::check($promotion);
    }
}
