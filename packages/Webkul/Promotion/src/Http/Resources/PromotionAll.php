<?php

namespace Webkul\Promotion\Http\Resources;

use App\Http\Resources\CustomResourceCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PromotionAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return Collection
     */
    public function toArray($request)
    {
        return  $this->collection->map(function ($promotion) {

            return [
                'id'            => $promotion->id,
                'title'         => $promotion->title,
                'promo_code'         => $promotion->promo_code,
                'description'         => $promotion->description,
                'discount_type'         => $promotion->discount_type,
                'discount_value'         => $promotion->discount_value,
                'start_validity'         => $promotion->start_validity ? Carbon::parse($promotion->start_validity)->format('d-m-Y h:i A') : null,
                'end_validity'         => $promotion->end_validity ? Carbon::parse($promotion->end_validity)->format('d-m-Y h:i A') : null,
                'minimum_order_amount'         => $promotion->minimum_order_amount,
                'minimum_items_quantity'         => $promotion->minimum_items_quantity,
                'apply_type'         => $promotion->apply_type,
                'apply_on'         => $promotion->apply->model_type,
                'apply_items'         => new PromotionApplyTypes($promotion->getPromotionApplyType, $promotion->apply_type),
                'exceptions_items'         => $promotion->getExceptionItems() ? new PromotionExceptionItems($promotion->getExceptionItems(), $promotion->apply_type) : null,
                'is_valid'         => $promotion->is_valid,
            ];
        });
    }

}
