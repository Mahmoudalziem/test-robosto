<?php

namespace Webkul\Admin\Http\Resources\Promotion;

use App\Http\Resources\CustomResourceCollection;

class PromotionAll extends CustomResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {

        return  $this->collection->map(function ($promotion) {

            return [
                'id'            => $promotion->id,
                'areas'         => $promotion->areas->pluck('name')->toArray(),
                'tags'         => $promotion->tags->pluck('name')->toArray(),
                'title'         => $promotion->title,
                'promo_code'         => $promotion->promo_code,//promo_code
                'description'         => $promotion->description,
                'discount_type'         => $promotion->discount_type,
                'discount_value'         => $promotion->discount_value,
                'start_validity'         => $promotion->start_validity,
                'end_validity'         => $promotion->end_validity,
                'promo_validity'         => $promotion->start_validity?$promotion->start_validity . ' to '.$promotion->end_validity:null,
                'total_vouchers'         => $promotion->total_vouchers,
                'usage_vouchers'         => $promotion->usage_vouchers,
                'minimum_order_amount'         => $promotion->minimum_order_amount,
                'minimum_items_quantity'         => $promotion->minimum_items_quantity,
                'total_redeems_allowed'         => $promotion->total_redeems_allowed,
                'price_applied'         => $promotion->price_applied,
                'apply_type'         => $promotion->apply_type,
                'exceptions_items'         => $promotion->exceptions_items,
                'send_notifications'         => $promotion->send_notifications,
                'is_valid'         => $promotion->is_valid,
                'show_in_app'         => $promotion->show_in_app,
                'status'         => $promotion->status,
                'created_at'    => $promotion->created_at,
            ];
        });
    }

}