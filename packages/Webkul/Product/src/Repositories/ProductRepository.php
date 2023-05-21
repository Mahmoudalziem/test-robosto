<?php

namespace Webkul\Product\Repositories;

use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Eloquent\Repository;
use Webkul\Promotion\Models\Promotion;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\PromotionValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Webkul\Promotion\Services\ApplyPromotion\ApplyPromotion;

class ProductRepository extends Repository
{

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Product\Contracts\Product';
    }

    /**
     * Search in Purchase Orders
     * @param $request
     * @return LengthAwarePaginator
     */
    public function search($request)
    {
        $perPage = 100;

        // Search by Barcode
        if ($request->has('barcode') && !empty($request->barcode)) {
            $pagination = Product::active()->hasAmount()
                ->where('barcode', $request['barcode'])->paginate($perPage);
            // Search by Name
        } else {
            $pagination = Product::search(trim($request['query']))->query(function ($query) {
                return $query->active()->hasAmount();
            })->paginate($perPage);
        }

        $pagination->appends([
            'per_page' => $request->per_page,
        ]);

        return $pagination;
    }

    /**
     * Search in Purchase Orders
     * @param $request
     * @param $subCategory
     * @return Product
     */
    public function productsBySubCategory($request, $subCategory)
    {
        $query = $subCategory->products();

        $query = $query->active()->hasAmount();
        $query = $query->orderBy('product_sub_categories.id', 'desc');
        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $perPage = 50;
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
    public function paymentSummary(array $data, Promotion $promotion = null)
    {
        $total = 0;

        foreach ($data['items'] as $item) {
            $margin = isset($item['margin']) ? $item['margin'] : 0;
            $product = $this->model->find($item['id']);
            $product->price = $product->price + ($product->price * $margin );
            $total += $this->calculateProductPrice($product, $item['qty']);

            // $total += $product->price * $item['qty'];
        }

        $amountToPay = $total;
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
            'basket_total' => $total,
            'balance' => (float) $customerWallet,
            'amount_to_pay' => $amountToPay
        ];

        // Handle Promotion Code
        $summary = $this->handlePromoCode($data, $summary, $promotion);

        // Handle Referral Code on the First Order
        $summary = $this->handleCustomerFirstOrder($data, $summary);

        // Handle Delivery fees
        $deliverFees = $this->handleDeliveryFees($data, $summary['basket_total']);
        $deliverFees = $this->handleFreeShippingCoupon($data, $deliverFees);
        $summary['amount_to_pay'] += $deliverFees;
        $summary['delivery_fees'] = $deliverFees;

        // Handle Long Decimal Numbers
        $summary['amount_to_pay'] = round($summary['amount_to_pay'], 2);
        if (isset($summary['discount'])) {
            $summary['discount'] = round($summary['discount'], 2);
        }


        return $summary;
    }

    /**
     * @param array $data
     * @param float $summary
     *
     * @return float
     */
    private function handleDeliveryFees(array $data, float $total)
    {
        $deliverFees = config('robosto.DELIVERY_CHARGS');

        if ($total >= config('robosto.MINIMUM_ORDER_AMOUNT')) {
            return 0;
        }
        return $deliverFees;
    }

    /**
     * @param array $data
     * @param float $fees
     *
     * @return float
     */
    private function handleFreeShippingCoupon(array $data, float $fees)
    {
        if (isset($data['free_shipping']) && $data['free_shipping'] == true) {
            $promotion = Promotion::where('promo_code', config('robosto.FREE_SHIPPING_COUPON'))->first();
            if ($promotion) {
                $customer = $data['customer'];

                // Check Customer Tags Exist Promotion Tags
                $customerTags = $customer->tags()->pluck('tags.id')->toArray();
                $promotionTags = $promotion->tags()->pluck('tags.id')->toArray();

                // Check Customer has at least Tag in Promotion Tags
                if (count(array_intersect($customerTags, $promotionTags)) == 0) {
                    Log::info('Free Shipping Coupon Not Valid For Tag');
                    throw new PromotionValidationException(406, __('customer::app.notValidPromoCode'));
                }

                return 0;
            }
        }

        return $fees;
    }

    /**
     * Handle Promotion Code
     *
     * @param array $data
     * @param array $summary
     *
     * @return array
     */
    public function handlePromoCode(array $data, array $summary, Promotion $promotion = null)
    {
        $items = $data['items'];
        $amountToPay = $summary['amount_to_pay'];

        // Handle Promotion Code
        if ($promotion) {
            // Apply Promotion On Items
            $applyPromotion = new ApplyPromotion($promotion, $items);
            $products = $applyPromotion->apply();

            // Just Apply Discount on Applicable given Items
            if (!empty($products['discounted_items'])) {

                $summary['coupon'] = $data['promo_code'];
                $discount = $this->totalPriceDiscounted($products['discounted_items']);

                $summary['discount'] = $discount;
                if ($amountToPay > $discount) {
                    $summary['amount_to_pay'] -= $discount;
                } else {
                    $summary['amount_to_pay'] = 0;
                }
            }
        }
        return $summary;
    }

    /**
     * Handle Referral Code on the First Order
     *
     * @param array $data
     * @param array $summary
     *
     * @return array
     */
    public function handleCustomerFirstOrder(array $data, array $summary)
    {
        // Handle Referral Code on the First Order
        if (isset($data['customer'])) {

            $customer = $data['customer'];

            // if the first order
            if ($customer->invitation_applied == 0 && !is_null($customer->invited_by)) {

                // if this customer already toked 25% discount
                $customerActiveOrders = $customer->activeOrders;
                if ($customerActiveOrders->isNotEmpty()) {
                    return $summary;
                }
                Log::info($summary);
                $summary = $this->handleExcludedCategories($data, $summary);
                Log::info($summary);
                // Apply 25% Discount on the Order Total
                $percentage = config('robosto.ORDER_INVITE_CODE_GIFT');
                $discount = ($percentage / 100) * $summary['amount_after_exclude'];

                $summary['discount'] = $discount;
                $summary['coupon'] = $customer->invitedBy->referral_code;
                $summary['amount_to_pay'] -= $discount;

                unset($summary['amount_after_exclude']);
            }
        }

        return $summary;
    }

    /**
     * @param array $data
     * @param array $summary
     *
     * @return array
     */
    public function handleExcludedCategories(array $data, array $summary)
    {
        $excludedCategories = config('robosto.EXCLUDED_CATEGORIES');
        $excludedProductPrices = 0;
        $summary['amount_after_exclude'] = $summary['amount_to_pay'];
        foreach ($data['items'] as $item) {

            $check = Product::where('id', $item['id'])->whereHas('subCategories', function (Builder $query) use ($excludedCategories) {
                $query->whereHas('parentCategories', function (Builder $query) use ($excludedCategories) {
                    $query->whereIn('category_id', $excludedCategories);
                });
            })->first();

            if ($check) {
                $excludedProductPrices += ($check->price * $item['qty']);
            }
        }

        $summary['amount_after_exclude'] -= $excludedProductPrices;

        return $summary;
    }

    /**
     * @param array $items
     *
     * @return int|float
     */
    private function totalPriceDiscounted(array $items)
    {
        $totalPriceDiscounted = 0;
        foreach ($items as $item) {
            $totalPriceDiscounted += ($item['total_price'] - $item['total_discounted_price']);
        }
        return $totalPriceDiscounted;
    }

    /**
     * @param Product $product
     * @param int $qty
     *
     * @return int|float
     */
    private function calculateProductPrice(Product $product, int $qty)
    {
        $total = 0;

        if ($product->discount_details) {
            $selfDiscount = $this->checkDiscountValidity($product);

            // if discount is valid
            if ($selfDiscount) {
                // calculate total from discount details
                $total += $this->calculateDiscountForProduct($product, $selfDiscount, $qty);
            } else {
                $total += $product->price * $qty;
            }
        } else {
            $total += $product->price * $qty;
        }

        return $total;
    }

    /**
     * @param Product $product
     *
     * @return mixed
     */
    private function checkDiscountValidity(Product $product)
    {
        return collect($product->discount_details)
            ->where('start_validity', '<=', now()->format('Y-m-d H:i'))
            ->where('end_validity', '>=', now()->format('Y-m-d H:i'))
            ->where('area_id', request()->header('area'))
            ->first();
    }

    /**
     * @param Product $product
     * @param array $selfDiscount
     * @param int $qty
     *
     * @return mixed
     */
    private function calculateDiscountForProduct(Product $product, array $selfDiscount, int $qty)
    {
        $total = 0;
        // if discount has maximum amount
        if ($selfDiscount['discount_qty']) {
            // if requested amount is larger than discounted qty
            // requested amount = 10, discounted qty = 6, original price = 10, discount price = 8
            if ($qty > $selfDiscount['discount_qty']) { // 10 > 6
                // divide qty into two parts
                $discountedQty = $qty - $selfDiscount['discount_qty'];  // 10 - 6 = 4
                $total += $selfDiscount['discount_price'] * $selfDiscount['discount_qty']; // 8 * 6
                $total += $product->price * $discountedQty;     // 10 * 4
            } else {

                $total += $selfDiscount['discount_price'] * $qty;
            }
        } else {
            $total += $selfDiscount['discount_price'] * $qty;
        }

        return $total;
    }
}
