<?php


namespace Webkul\Promotion\Services\PromotionValidation;


use Webkul\Promotion\Models\Promotion;

abstract class PromotionRule
{
    /**
     * @var PromotionRule
     */
    private $next;

    /**
     * This method can be used to build a chain of middleware objects.
     * @param PromotionRule $next
     * @return PromotionRule
     */
    public function setNext(PromotionRule $next): PromotionRule
    {
        $this->next = $next;

        return $next;
    }

    /**
     * Subclasses must override this method to provide their own checks. A
     * subclass can fall back to the parent implementation if it can't process a
     * request.
     * @param Promotion $promotion
     * @return bool
     */
    public function check(Promotion $promotion): bool
    {
        if (!$this->next) {
            return true;
        }
        return $this->next->check($promotion);
    }
}