<?php
namespace Webkul\Promotion\Services\PromotionValidation\Rules;


use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\Log;
use Webkul\Promotion\Models\Promotion;
use Webkul\Promotion\Services\PromotionValidation\PromotionRule;

class VouchersCount extends PromotionRule
{

    /**
     * @var Order
     */
    private $order;

    /**
     * @param Order|null $order
     */
    public function __construct(Order $order = null)
    {
        $this->order = $order;
    }

    /**
     * Check Promotion Valid from date to date
     * @param Promotion $promotion
     * @return bool
     */
    public function check(Promotion $promotion): bool
    {
        $usagVouchers = $promotion->usage_vouchers;
        if (!is_null($this->order) && $this->order->promotion_id == $promotion->id) {
            $usagVouchers -= 1;
        }

        // Check Total number of vouchers is exceeded or Not
        if ($promotion->total_vouchers == $usagVouchers) {
            Log::info('Not Valid For Vouchers');
            return false;
        }

        return parent::check($promotion);
    }
}