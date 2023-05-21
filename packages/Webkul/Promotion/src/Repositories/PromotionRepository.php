<?php

namespace Webkul\Promotion\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Promotion\Models\Promotion;
use Webkul\Sales\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Webkul\Promotion\Models\PromotionVoidDevice;

class PromotionRepository extends Repository {

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model() {
        return 'Webkul\Promotion\Contracts\Promotion';
    }

    /**
     * Search in Purchase Orders
     * @param $request
     * @return LengthAwarePaginator
     */
    public function list($request) {
        $query = $this->newQuery();

        if ($request->header('area')) {
            $query->whereHas('areas', function ($q) use ($request) {
                $q->where('areas.id', '=', $request->header('area'));
            });
        }

        // Filter Valid Promotion Only
        $query->active()->valid()->showInApp();

        // if the customer authed, then load tags related to customer tags
        if (auth('customer')->check()) {

            $customerTags = auth('customer')->user()->tags->pluck('id')->toArray();

            $query->whereHas('tags', function (Builder $query) use ($customerTags) {
                $query->whereIn('tags.id', $customerTags);
            });
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;

        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

    /**
     * Update Used Promotion
     * 
     * @param Promotion $promotion
     * 
     * @return bool
     */
    public function updateUsedPromotion(Promotion $promotion) {
        $usedVouchers = $promotion->usage_vouchers;

        if ($usedVouchers + 1 == $promotion->total_vouchers) {
            $promotion->is_valid = 0;
        }

        $promotion->usage_vouchers += 1;
        $promotion->save();
    }

    /**
     * Decrease Used Promotion
     * 
     * @param Promotion $promotion
     * 
     * @return void
     */
    public function decreaseUsedPromotion(Promotion $promotion) {
        $promotion->usage_vouchers -= 1;
        $promotion->save();
    }

    /**
     * Decrease Used Order
     * 
     * @param Order $order
     * 
     * @return void
     */
    public function removePromotionDeviceid(Order $order) {
        Log::info('removePromotionDeviceid -> ' . $order->id . ' custid :' . $order->customer_id);
        if ($order->promotionDevice) {
            $order->promotionDevice->delete();
        }
    }

}
