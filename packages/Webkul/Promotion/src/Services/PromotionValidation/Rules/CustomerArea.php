<?php


namespace Webkul\Promotion\Services\PromotionValidation\Rules;


use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Webkul\Promotion\Models\Promotion;
use Webkul\Promotion\Services\PromotionValidation\PromotionRule;

class CustomerArea extends PromotionRule
{
    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var int
     */
    private $areaId;

    /**
     * Tags constructor.
     * @param Customer $customer
     * @param int $areaId
     */
    public function __construct(Customer $customer, int $areaId)
    {
        $this->customer = $customer;
        $this->areaId = $areaId;
    }

    /**
     * Check Promotion Valid from date to date
     * @param Promotion $promotion
     * @return bool
     */
    public function check(Promotion $promotion): bool
    {
        // Check Address belongs To Promotion Area
        $givenAreaCheck = $promotion->areas()->where('areas.id', $this->areaId)->first();
        if (!$givenAreaCheck) {
            Log::info('Not Valid For Area');
            return false;
        }

        return parent::check($promotion);
    }
}