<?php

namespace Webkul\Promotion\Services\PromotionValidation\Rules;

use Illuminate\Support\Facades\Log;
use Webkul\Promotion\Models\Promotion;
use Webkul\Promotion\Services\PromotionValidation\PromotionRule;
use Webkul\Promotion\Models\PromotionVoidDevice;

class MaxAllowedDevice extends PromotionRule {

    /**
     * @var array
     */
    private $deviceid;

    /**
     * MinimumOrderRequirements constructor.
     * @param float $totalOrderPrice
     * @param int $totalItemsQuantity
     */
    public function __construct($deviceId) {
        $this->deviceid = $deviceId;
    }

    /**
     * Check Promotion Valid from date to date
     * @param Promotion $promotion
     * @return bool
     */
    public function check(Promotion $promotion): bool {

        if (!is_null($promotion->max_device_count)) {
            // find if this deviced has been used before
           $deviceidCount=PromotionVoidDevice::where(['promotion_id'=>$promotion->id,'deviceid'=>$this->deviceid])->whereNotNull('deviceid')->count();

            if ($deviceidCount >= $promotion->max_device_count) {
                Log::info('This device has been used with selected promotion! ');
                return false;
            }
        }

        return parent::check($promotion);
    }

}
