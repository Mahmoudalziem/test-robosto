<?php

namespace Webkul\Bundle\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Webkul\Core\Eloquent\Repository;
use Webkul\Bundle\Models\Bundle;
use Webkul\Promotion\Models\Promotion;

class BundleRepository extends Repository
{

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Bundle\Contracts\Bundle';
    }

    /**
     * Search in Purchase Orders
     * @param $request
     * @return LengthAwarePaginator
     */
    public function search($request)
    {
        $perPage = $request->has('per_page') ? (int)$request->per_page : null;
        // Search by Name
        if ($request->has('query') && !empty($request->query)) {
            $key =  $request['query'];
        }

        // Search by Barcode
        if ($request->has('barcode') && !empty($request->barcode)) {
            $key =  $request['barcode'];
        }

        $pagination =  Bundle::search(trim($key))->query(function ($query) {
            return $query->active()->hasAmount();
        })->paginate($perPage);
        $pagination->appends([
            'per_page' => $request->per_page,
        ]);

        return $pagination;
    }


    /**
     * Search in Purchase Orders
     * @param $request
     * @param $subCategory
     * @return Bundle
     */
    public function bundlesBySubCategory($request, $subCategory)
    {
        $query = $subCategory->bundles();

        $query = $query->active()->hasAmount();

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }


    /** Calculate Payment Summary
     * @param array $data
     * @return array
     */
    public function paymentSummary(array $data)
    {
        $deliver_fees = config('robosto.DELIVERY_CHARGS');
        $total = 0;

        foreach ($data['items'] as $item) {
            $bundle = $this->model->find($item['id']);
            $total += $bundle->price * $item['qty'];
        }

        $amountToPay = $total + $deliver_fees;
        $customerWallet = 0;

        // Apply Wallet if user Exist
        if (auth('customer')->check()) {
            $customerWallet = auth('customer')->user()->wallet;
            $amountToPay -= $customerWallet;
        }

        if ($amountToPay < 0) {
            $amountToPay = 0;
        }

        $summary = [
            'basket_total'      =>  $total,
            'delivery_fees'     =>  (int) $deliver_fees,
            'balance'           =>  (float) $customerWallet,
            'amount_to_pay'     =>  $amountToPay
        ];

        if (isset($data['promo_code']) && !empty($data['promo_code'])) {
            // Get the Promotion
            $promotion = Promotion::where('promo_code', $data['promo_code'])->first();

            if ($promotion->discount_type == Promotion::DISCOUNT_TYPE_VALUE) { // 10 L.E
                $discount = $promotion->discount_value;
            } else {
                $discount = ($promotion->discount_value / 100) * $total;
            }

            if ($amountToPay > $discount) {
                $summary['discount']  = $discount;
                $summary['amount_to_pay']  -= $discount;
            } else {
                $summary['discount']  = $discount;
            }
        }

        return $summary;
    }
}
