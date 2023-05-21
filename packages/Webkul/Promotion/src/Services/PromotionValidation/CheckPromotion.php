<?php


namespace Webkul\Promotion\Services\PromotionValidation;


use Illuminate\Support\Facades\Log;
use Webkul\Promotion\Models\Promotion;
use App\Exceptions\PromotionValidationException;

class CheckPromotion
{
    /**
     * @var Promotion
     */
    public $promotion;

    /**
     * @var PromotionRule
     */
    private $promotionRule;

    /**
     * CheckPromotion constructor.
     * @param Promotion $promotion
     */
    public function __construct(Promotion $promotion)
    {
        $this->promotion = $promotion;
    }

    /**
     * CheckPromotion constructor.
     * @param PromotionRule $promotionRule
     */
    public function setRule(PromotionRule $promotionRule)
    {
        $this->promotionRule = $promotionRule;
    }

    /**
     * CheckPromotion constructor.
     * @return bool
     */
    public function checkPromotionIsValid()
    {
        if ($this->promotionRule->check($this->promotion)) {
            Log::info('Promo-Pass');
            return true;
        }
        Log::info('Promo-Failed');
        throw new PromotionValidationException(406, __('customer::app.notValidPromoCode'));
    }

}