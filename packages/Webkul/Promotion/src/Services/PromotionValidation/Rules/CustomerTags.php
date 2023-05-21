<?php


namespace Webkul\Promotion\Services\PromotionValidation\Rules;


use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Webkul\Promotion\Models\Promotion;
use Webkul\Promotion\Services\PromotionValidation\PromotionRule;

class CustomerTags extends PromotionRule
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
        // Check Customer Tags Exist Promotion Tags
        $customerTags = $this->customer->tags()->pluck('tags.id')->toArray();
        $promotionTags = $promotion->tags()->pluck('tags.id')->toArray();

        // Check Customer has at least Tag in Promotion Tags
        if (count(array_intersect($customerTags, $promotionTags)) == 0) {
            Log::info('Not Valid For Tag');
            return false;
        }

        // Check Customer belongs To Promotion Tag
        return parent::check($promotion);
    }
}