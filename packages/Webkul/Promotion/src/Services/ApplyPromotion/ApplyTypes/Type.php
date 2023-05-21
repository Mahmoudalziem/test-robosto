<?php
namespace Webkul\Promotion\Services\ApplyPromotion\ApplyTypes;

use Webkul\Promotion\Models\Promotion;


interface Type
{
    /**
     * @param Promotion $promotion
     * @param array $items
     */
    public function apply(Promotion $promotion, array $items);
}