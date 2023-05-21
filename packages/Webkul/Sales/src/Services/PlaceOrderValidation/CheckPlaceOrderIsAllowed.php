<?php
namespace Webkul\Sales\Services\PlaceOrderValidation;


use App\Exceptions\PlaceOrderValidationException;
use Webkul\Sales\Models\Order;

class CheckPlaceOrderIsAllowed
{

     /**
      * @var array
      */
     public $items;

    /**
     * @var PlaceOrderRule
     */
    private $placeOrderRule;

    /**
     * CheckOrder constructor.
     * @param Order $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * CheckOrder constructor.
     * @param PlaceOrderRule $placeOrderRule
     */
    public function setRule(PlaceOrderRule $placeOrderRule)
    {
        $this->placeOrderRule = $placeOrderRule;
    }

    /**
     * CheckOrder constructor.
     * @return bool
     */
    public function checkPlaceOrderIsAllowed()
    {

        if ($this->placeOrderRule->check($this->items)) {
            return true;
        }

        throw new PlaceOrderValidationException(410, 'Some Errors Happens');
    }

}