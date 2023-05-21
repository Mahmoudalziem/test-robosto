<?php

namespace Webkul\Sales\Services\PlaceOrderValidation\Rules;

use Carbon\Carbon;
use App\Exceptions\PlaceOrderValidationException;
use Webkul\Sales\Services\PlaceOrderValidation\PlaceOrderRule;

class CheckScheduleTimeRule extends PlaceOrderRule
{
    /**
     * @var array
     */
    private $data;

    /**
     * Tags constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Check Customer cannot Place Order within 30 minutes
     * @param array $items
     * @return mixed
     */
    public function check(array $items)
    {
        // Get Houts Buffer
        $hours = config('robosto.ORDER_SCHEDULE_TIME_BUFFER');
        $data = $this->data;
        // if schedule time gived
        if (isset($data['scheduled_at']) && !empty($data['scheduled_at']) && $data['scheduled_at'] != 0) {

            $givinTime =  Carbon::createFromTimestamp($data['scheduled_at']);

            if ($givinTime < now()->addHours($hours)) {
                throw new PlaceOrderValidationException(410, __('sales::app.shcedulTimeNotValid')); 
            }

        }

        return parent::check($items);
    }
}