<?php


namespace Webkul\Sales\Services\PlaceOrderValidation;

use Webkul\Sales\Models\Order;

abstract class PlaceOrderRule
{
    /**
     * @var PlaceOrderRule
     */
    private $next;

    /**
     * This method can be used to build a chain of middleware objects.
     * @param PlaceOrderRule $next
     * @return PlaceOrderRule
     */
    public function setNext(PlaceOrderRule $next): PlaceOrderRule
    {
        $this->next = $next;

        return $next;
    }

    /**
     * Subclasses must override this method to provide their own checks. A
     * subclass can fall back to the parent implementation if it can't process a
     * request.
     * @param Order $promotion
     * @return mixed
     */
    public function check(array $items)
    {
        if (!$this->next) {
            return true;
        }
        return $this->next->check($items);
    }
}